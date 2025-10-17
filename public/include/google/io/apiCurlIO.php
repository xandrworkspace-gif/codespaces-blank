<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Curl based apiIO implementation.
 * This class implements http spec compliant request caching using the apiCache class
 *
 * @author Chris Chabot <chabotc@google.com>
 */
class apiCurlIO implements apiIO {
  // Set by the top level apiClient class, stored here locally to deal with auth signing and caching
  private $cache;
  private $auth;

  /**
   * Called by the apiClient base class
   * @param apiCache $cache
   * @param apiAuth $auth
   */
  public function __construct(apiCache $cache, apiAuth $auth) {
    $this->cache = $cache;
    $this->auth = $auth;
  }

  /**
   * Perform an authenticated / signed apihttpRequest.
   * This function takes the apiHttpRequest, calls apiAuth->sign on it (which can modify the request in what ever way fits the auth mechanism)
   * and then calls apiCurlIO::makeRequest on the signed request
   *
   * @param apiHttpRequest $request
   * @return apiHttpRequest the resulting request with the responseHttpCode, responseHeaders and responseBody filled in
   */
  public function authenticatedRequest(apiHttpRequest $request) {
    $request = $this->auth->sign($request);
    return $this->makeRequest($request);
  }

  /**
   * Execute a apiHttpRequest
   *
   * @param apiHttpRequest $request the http request to be executed
   * @return apiHttpRequest http request with the response http code, response headers and response body filled in
   * @throws apiIOException on curl or IO error
   */
  public function makeRequest(apiHttpRequest $request) {
  	$headers = $request->getHeaders();
  	$headers[] = 'Content-Type: application/x-www-form-urlencoded';
  
  	$content = $request->getPostBody();
    if( is_array($content) ) {
      $content = common_build_request($content);
    }
  	
  	$contentLength = strlen($content);
  	$headers[] = 'Content-Length: ' . $contentLength;
  	$headers[] = 'Length: ' . $contentLength;
  	
  	$request->setHeaders($headers);
  	$context = stream_context_create(array(
                  'http' => array(
                          'method'  => $request->getMethod(),
                          'header'  => implode("\r\n", $headers),
                          'content' => $content,
                  )
  	));
  	
  	$result = file_get_contents($request->getUrl(), false, $context);
  	
  	if( $result ) {
  		$request->setResponseHttpCode(200);
  	}
  
  	$request->setResponseBody($result);
  	return $request;
  }
  
  private function setCachedRequest(apiHttpRequest $request) {
    // Only cache 'GET' requests
    if ($request->getMethod() != 'GET') {
      return false;
    }
    // Analyze the request's headers to see if there is a valid caching strategy
    $headers = $this->getNormalizedHeaders($request);
    // And parse all the bits that are required for the can-cache evaluation
    $etag = isset($headers['etag']) ? $headers['etag'] : false;
    $expires = isset($headers['expires']) ? strtotime($headers['expires']) : false;
    $date = isset($headers['date']) ? strtotime($headers['date']) : time();
    $cacheControl = array();
    if (isset($headers['cache-control'])) {
      $cacheControl = explode(', ', $headers['cache-control']);
      foreach ($cacheControl as $key => $val) {
        $cacheControl[$key] = strtolower($val);
      }
    }
    $pragmaNoCache = isset($headers['pragma']) ? strtolower($headers['pragma']) == 'no-cache' : false;
    // evaluate if the request can be cached
    $canCache = ! in_array('no-store', $cacheControl) &&                                            // If no Cache-Control: no-store is present, we can cache
              (($etag || $expires > $date) ||                                                       // if the response has an etag, or if it has an expiration date that is greater then the current date, we can check for a 304 NOT MODIFIED, so cache
              (! $etag && ! $expires && ! $pragmaNoCache && ! in_array('no-cache', $cacheControl))); // or if there is no etag, and no expiration set, but also no pragma: no-cache and no cache-control: no-cache, we can cache (but we'll set our own expires header to make sure it's refreshed frequently)
    if ($canCache) {
      // Set an 1 hour expiration header if non exists, and no do-not-cache directives exist
      if (! $etag && ! $expires && ! $pragmaNoCache && ! in_array('no-cache', $cacheControl)) {
        // Add Expires and Date headers to simplify the cache retrieval code path
        $request->setResponseHeaders(array_merge(array(
            'Expires' => date('r', time() + 60 * 60),
            'Date' => date('r', time())), $request->getHeaders()));
      }
      $this->cache->set($this->getRequestKey($request), $request);
    }
  }

  private function getCachedRequest(apiHttpRequest $request) {
    if (($cachedRequest = $this->cache->get($this->getRequestKey($request))) !== false) {
      // There is a cached version of this request, validate if it can actually be used
      $headers = $this->getNormalizedHeaders($request);
      $etag = isset($headers['etag']) ? $headers['etag'] : false;
      $expires = isset($headers['expires']) ? strtotime($headers['expires']) : false;
      $date = isset($headers['date']) ? strtotime($headers['date']) : time();
      $cacheControl = array();
      if (isset($headers['cache-control'])) {
        $cacheControl = explode(', ', $headers['cache-control']);
        foreach ($cacheControl as $key => $val) {
          $cacheControl[$key] = strtolower($val);
        }
      }
      // Only use the cached request if it has an etag or expiration date that's lower then the current time
      if ($etag || ($expires < $date)) {
        // There is either an ETag set, or the expiration time is less then the current time, return it
        return $cachedRequest;
      } else {
        // Clean out the stale cache entry before returning
        $this->cache->delete($this->getRequestKey($request));
      }
    }
    // Either the request was not cached, or it has expired, return false
    return false;
  }

  /**
   * Returns true if the request has specified must-revalidate in it's Cache-Control header, or if it doesn't have an Expires header but does have an ETag or has expired
   * @param apiHttpRequest $request
   * @return boolean
   */
  private function mustRevalidate(apiHttpRequest $request) {
    // check to see if we need to go the If-Modified-Since or Etag route (in which case we make the request, but accept a 304 NOT MODIFIED)
    $headers = $this->getNormalizedHeaders($request);
    $etag = isset($headers['etag']) ? $headers['etag'] : false;
    $expires = isset($headers['expires']) ? strtotime($headers['expires']) : false;
    $date = isset($headers['date']) ? strtotime($headers['date']) : time();
    $cacheControl = array();
    if (isset($headers['cache-control'])) {
      $cacheControl = explode(', ', $headers['cache-control']);
      foreach ($cacheControl as $key => $val) {
        $cacheControl[$key] = strtolower($val);
      }
    }
    return (in_array('must-revalidate', $cacheControl) || ($etag && ! $expires) || $expires > $date);
  }

  /**
   * Returns a cache key depending on if this was an OAuth signed request in which case it will use the non-signed url and access key to make this caching key unique
   * per authenticated user, else use the plain request url
   * @param apiHttpRequest $request
   * @return a md5 sum of the request url
   */
  private function getRequestKey(apiHttpRequest $request) {
    $cacheUrl = (isset($request->accessKey) ? $request->getUrl(). '.' . $request->accessKey : $request->getUrl());
    return md5($cacheUrl);
  }

  private function getNormalizedHeaders(apiHttpRequest $request) {
    if (!is_array($request->getResponseHeaders())) {
      return array();
    }
    $headers = $request->getResponseHeaders();
    $newHeaders = array();
    foreach ($headers as $key => $val) {
      $newHeaders[strtolower($key)] = $val;
    }
    return $newHeaders;
  }
}
