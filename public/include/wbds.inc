<?php /* $Id: wbds.inc,v 1.13 2007-12-06 08:52:00 sen Exp $ */

require_once(dirname(__FILE__)."/pf.inc");

  define('WBDS_DISC', 1); /* Disconnected state */
  define('WBDS_CONN', 2); /* Object is connected */
  define('WBDS_FAIL', -1); /* Reconnection count exceeded, looks like network failure. Recreate class object to continue */

  define('WBDS_CONN_TIMEOUT', 2); /* Connect timeout */
  define('WBDS_MAX_ATTEMPTS', 1); /* Maximum number of reconnections */
  define('WBDS_MAX_ATTEMPTS_SAME_SERVER', 1); /* Maximum number of reconnections */

  define('CMD_INVALID',     0x00);
  define('CMD_NOOP',        0x01);
  define('CMD_SLEEP',       0x02);
  define('CMD_CREATEOBJ',   0x03);
  define('CMD_DESTROYOBJ',  0x04);
  define('CMD_GETOBJPROP',  0x05);
  define('CMD_SETOBJPROP',  0x06);
  define('CMD_UNSETOBJPROP',0x07);
  define('CMD_GETOBJLINK',  0x08);
  define('CMD_ADDOBJLINK',  0x09);
  define('CMD_DELOBJLINK',  0x0A);
  define('CMD_DUMPOBJ',     0x0B);
  define('CMD_DEBUGLEVEL',  0x0C);
  define('CMD_CHECKOBJ',    0x0D);
  define('CMD_UPLOADOBJ',   0x0E);
  define('CMD_SETAUTOINC',  0x0F);
  define('CMD_GETOBJLIST',  0x10);
  define('CMD_DUMPALL',     0x11);
  define('CMD_SHUTDOWN',    0x12);
  define('CMD_SETOBJTPROPS',0x13);

  //define('WBDS_DEBUG', 1 );

  include_once('include/object.inc');
  include_once('include/objects.inc');

  global $argFormats;
  $argFormats = array
    (
      AT_INT => "%08X",
      AT_BOOL => "%08X",
      AT_FLOAT => "%016.4f",
      AT_STRING => "%s",
      AT_INVALID => "%08X",
      AT_OBJECT => "%s"
    );
  global $argLessCommands;
  $argLessCommands = array(CMD_GETOBJLIST, CMD_DUMPALL, CMD_SHUTDOWN, CMD_SETOBJTPROPS);

  //  For 64-bit safety on sending data. Not generally reusable.
  define('STSFMT_RAW',    0);
  define('STSFMT_STRING', 1);
  define('STSFMT_LONG',   2);
  define('STSFMT_SHORT',  3);
  define('STSFMT_FLOAT',  4);

  $argTypeToSTSFMT = array(
      AT_INT      => STSFMT_LONG,
      AT_BOOL     => STSFMT_LONG,
      AT_FLOAT    => STSFMT_FLOAT,
      AT_STRING   => STSFMT_STRING,
      AT_INVALID  => STSFMT_LONG,
      AT_OBJECT   => STSFMT_STRING,
    );

  function safe_tostring_($format_type, $arg) {
    //  The main problem is that 64bit php gives 0xffffffffffffffff for -1,
    //  if raw %08X is used.
    //  Do $arg type validation too.
    $str = false;
    switch ($format_type)
    {
    case STSFMT_LONG:
      if ($arg < 0) { $arg = 0xffffffff; }  //  Supporting only '-1'
      $str = sprintf("%08X", min($arg, 0xffffffff));
      break;

    case STSFMT_SHORT:
      if ($arg < 0) { $arg = 0xffff; }  //  Supporting only '-1'
      $str = sprintf("%04X", min($arg, 0xffff));
      break;

    case STSFMT_FLOAT:
      $str = sprintf("%016.4f", $arg);
      break;

    case STSFMT_STRING:
      $str = sprintf("%s", $arg);
      break;

    case STSFMT_RAW:  //  Fall through
    default:
      $str = $arg; // Would be converted by PHP itself.
    }
    return $str;
  }

  class WBDS
  {
    //
    //  Private variables
    //
    var $serverSocket, $serverPool, $objectStatus, $myPID, $serverURL, $sequenceId, $chunkSize;
    var $numReconnections;

    //
    //  Private functions
    //

    function &socket($newSocket = -1)  //!< @private
    {
      if ($newSocket != -1)
      {
        $this->serverSocket = $newSocket;
      };
      return $this->serverSocket;
    }

    function &status($newStatus = false) //!< @private
    {
      if ($newStatus !== false)
      {
        $this->objectStatus = $newStatus;
      };
      return $this->objectStatus;
    }

    function &pool($newPool = false) //!< @private
    {
      if ($newPool !== false)
      {
        $this->serverPool = $newPool;
      };
      return $this->serverPool;
    }

    function connectionFailure($needDisconnect = true) //!< @private
    {
      $this->log_error("Connection failure");
      $this->numReconnections++;
      if ($needDisconnect === true)
      {
        $this->safe_disconnect();
      }

      if ($this->numReconnections > WBDS_MAX_ATTEMPTS)
      {
        $this->log_error("Too many connection failures detected, looks like network failure. Recreate WBDS object to continue.");
        $this->status(WBDS_FAIL);
        return true;
      }

      return false;
    }

    function tryconnect()  //!< @private
    {
      global $PHP_SELF;
      if ($this->is_connected())
      {
        if ($this->socket() && !feof($this->socket()))
        {
          return true;
        }
        else
        {
          $this->log_error("tryconnect(): Was disconnected from socket, reconnecting to the same server: " . $this->serverURL);
          if ($this->socket())
          {
            fclose($this->socket());
          }
          for ($i = 0; $i < WBDS_MAX_ATTEMPTS_SAME_SERVER; ++$i)
          {
            if ($this->connect($this->serverURL))
            {
              $this->message("tryconnect(): Reconnected - $PHP_SELF");
              return true;
            }
          }

          $this->log_error("tryconnect(): Exceeded number of reconnection attempts");
          $this->connectionFailure();
        }
      };

      $hi1 = 0;

      $servers = $this->serverPool;
      shuffle($servers);

      foreach($servers as $server)
      {
        if ($this->connect($server) === true)
        {
          $this->message("tryconnect(): Connected - $PHP_SELF");
          return true;
        };
      }

      $this->log_error("tryconnect(): Failed to connect to any server from the pool");
      $this->connectionFailure($this->is_connected());

      return false;
    }

    function connect($serverURL = false)  //!< @private Called from tryconnect() only.
    {
    	PF_CALL();
      if ($this->is_failed())
      {
        $this->log_error("connect(): Can't connect to '$serverURL', status is WBDS_FAIL");
        return false;
      }

      $this->message("Connecting to $serverURL");
      if (!$serverURL || !is_string($serverURL))
      {
        $this->log_error("connect(): Invalid server url '$serverURL'");
        return false;
      }

      $urlParts = parse_url($serverURL);
      $errno = 0;
      $errStr = false;

      $sock = @fsockopen("tcp://".$urlParts['host'], $urlParts['port'], $errno, $errStr, WBDS_CONN_TIMEOUT);
      if (!$sock)
      {
        $this->log_error(__FILE__.":".__LINE__." - fsockopen(".$urlParts['host'].",".$urlParts['port'].") failed, error: $errno ($errStr)");
        PF_RET();
        return false;
      }

      $this->socket($sock);
      $this->status(WBDS_CONN);
      stream_set_timeout($this->socket(), WBDS_CONN_TIMEOUT);
      stream_set_write_buffer($this->socket(), 1280);
      $this->serverURL = $serverURL;
      $this->numReconnections = 0;
      PF_RET();
      return true;
    }

    function disconnect() //<! @private Left this one to avoid change of semantics. Remove if unused.
    {
      if ($this->bad() || (!$this->socket()))
      {
        $this->log_error(__FILE__.":".__LINE__." - invalid status");
        return false;
      };
      fclose($this->socket());
      return true;
    }

    function safe_disconnect() //<! @private
    {
      if ($this->socket())
      {
        fclose($this->socket());
        $this->socket(0);
        $this->status(WBDS_DISC);
        return true;
      }
      return false;
    }

    function needReconnection($message = "") //!< @private
    {
      $this->log_error("Reconnecting. Reason: $message");
      if ($this->socket())
      {
        fclose($this->socket());
        $this->socket(0);
        //  Note not setting disconnected status -- this way tryconnect would reconnect on the same server.
      }
    }

    //  NOTE: Used from getAnswer() only (in so far).
    //  NOTE: If this function returns error, most likely you can't do anything much except reconnecting.
    function buffered_read(&$buffer, $size = 0)  //!< @private
    {
      if ($this->bad())
      {
        $this->log_error("buffered_read(): invalid status");
        return false;
      };

      if (!is_integer($size) || $size < 0)
      {
        $this->log_error("buffered_read(): invalid buffer size");
        return false;
      }

      if ($size === 0)
      {
//        $this->log_error("buffered_read(): empty buffer size, skipping");
        return 0;
      };

      $buffer = "";
      do
      {
        if (feof($this->socket()))
        {
          $this->log_error("buffered_read(): Disconnected from server.");
          return false;
        }

        if ($this->chunkSize === 0)
        {
          $hs1 = fread($this->socket(), 8);

          if (strlen($hs1)==0)
          {
            $this->log_error("buffered_read(): Failed to read chunk size: $errno ($errStr)");
            return false;
          };
          $this->chunkSize = intval($hs1, 16);
          $this->alreadyRead = 0;

          if ($this->chunkSize <= 0)
          {
            $this->log_error("buffered_read(): Invalid chunk size: " . $this->chunkSize);
            return false;
          }
        };

        $toRead = min($this->chunkSize - $this->alreadyRead, $size);
        if ($toRead <= 0)
        {
          $this->log_error("buffered_read(): Invalid fread() arguments.");  //  Should not happen anymore.
          return false;
        };

        $hs1 = fread($this->socket(), $toRead);
        $hi1 = strlen($hs1);

        if (!$hi1)
        {
          $this->log_error("buffered_read(): Read fails: $errno ($errStr)");
          return false;
        };
        $buffer .= $hs1;
        $this->alreadyRead += $hi1;
        $size -= $hi1;
        if ($this->alreadyRead === $this->chunkSize)
        {
          $this->chunkSize = 0;
        };
      } while ($size > 0);

      return $size;
    }

    //  NOTE: Used from sendCmd only (in so far).
    function safe_write(&$buffer, $size = 0) //!< @private
    {
      if ($this->bad() || ($size < 0))
      {
        $this->log_error("safe_write(): invalid status or arguments");
        return false;
      }

      $written = 0;
      if ($size > 0)
      {

        $written = fwrite($this->socket(), $buffer, $size);

        if ($written === false)
        {
          $this->log_error("safe_write(): Failed to write data: $errno ($errStr)");
        }
      }
      else
      {
        $this->log_error("safe_write(): zero buffer size, skipping");
      }

      return $written;
    }

    function sendCmd($object = false, $command = CMD_INVALID, $objType = false, $objId = false, $cmdArgs = false)  //!< @private
    {
      global $argFormats, $argLessCommands;
    	PF_CALL();
      $this->currentChunkSize = 0;
      if ((!$object || !is_object($object)) && !in_array($command, $argLessCommands))
      {
        $this->log_error('sendCmd(): Invalid arguments');
        return false;
      };

      if (!$this->tryconnect())
      {
        $this->log_error('sendCmd(): Cannot connect');
        PF_RET();
        return false;
      };

      $command = $command;
      $objType = $objType;
      $objId = $objId;
      //$this->message("Command :".$hs1);
      $hi1 = is_array($cmdArgs) ? sizeof($cmdArgs) : 0;
      $nextArg = is_array($cmdArgs) ? reset($cmdArgs) : false;
      $argCount = 0;
      $inList = false;

      $argstrings = array();

      foreach ($cmdArgs as $key => $nextArg)
      {
        $argId = $argCount;
        $skipRecord = false;
        switch ($command)
        {
          case CMD_CREATEOBJ:
          case CMD_DESTROYOBJ:
          case CMD_CHECKOBJ:
          case CMD_SETAUTOINC:
          case CMD_DUMPOBJ:
          case CMD_DESTROYOBJ:
            $argType = AT_INT;
            $arg = 0;
            break;
          case CMD_GETOBJPROP:
          case CMD_UNSETOBJPROP:
            $argType = AT_INT;
            $argId = $arg = $nextArg;
            $skipRecord = $object->propType($arg) === false;
            break;
          case CMD_SETOBJPROP:
            $argId = $key;
            $arg = $nextArg;
            $argType = $object->propType($argId);
            $skipRecord = $argType === false;
            break;
          case CMD_GETOBJLINK:
          case CMD_ADDOBJLINK:
          case CMD_DELOBJLINK:
            $skipRecord = !is_array($nextArg) || (sizeof($nextArg) != 2);
            if ($skipRecord)
            {
              break;
            };
            $argId = reset($nextArg);
            $argType = AT_INT;
            $arg = next($nextArg);
            break;
          case CMD_UPLOADOBJ:
            $argId = reset($nextArg);
            $arg = next($nextArg);
            $argType = AT_INT;
            if (!$inList)
            {
              $setProp = next($nextArg);
              $argType = ($argId == AT_INVALID) && ($arg == AT_INVALID) && !$setProp ?
                          AT_INVALID : $setProp ? $object->propType($argId) : AT_INVALID;
              $inList = ($argId == AT_INVALID) && ($arg == AT_INVALID) && !$setProp;
            };
            break;
          case CMD_GETOBJLIST:
            $argId = $nextArg[0];
            $arg = $nextArg[1];
            $argType = AT_INT;
            break;
          case CMD_SETOBJTPROPS:
            $arg = $nextArg;
            $argType = AT_INT;
            break;
        };
        if ($skipRecord)
        {
          continue;
        };

        //$hs3 = isset($argFormats[$argType]) ? sprintf($argFormats[$argType], $arg) : $arg;
        global $argTypeToSTSFMT;
        $hs3 = isset($argFormats[$argType])
          ? safe_tostring_($argTypeToSTSFMT[$argType], $arg)
          : $arg
          ;

        //$argstrings[] = sprintf('%08X%04X%08X', $argId, $argType, strlen($hs3)) . $hs3;  //  Append next argument
        $argstrings[] = safe_tostring_(STSFMT_LONG,   $argId);
        $argstrings[] = safe_tostring_(STSFMT_SHORT,  $argType);
        $argstrings[] = safe_tostring_(STSFMT_LONG,   strlen($hs3));
        $argstrings[] = safe_tostring_(STSFMT_STRING, $hs3);

        //$this->message("Next arg:".$hs4.$hs3);

        $argCount++;
      };

      /*
      $hs1 = sprintf(
          '%08X%04X%08X%08X%04X', $this->sequenceId, $command, $objType, $objId, $argCount
        ) . implode('', $argstrings);
      */

      $hs1 =
          safe_tostring_(STSFMT_LONG,   $this->sequenceId)
        . safe_tostring_(STSFMT_SHORT,  $command)
        . safe_tostring_(STSFMT_LONG,   $objType)
        . safe_tostring_(STSFMT_LONG,   $objId)
        . safe_tostring_(STSFMT_SHORT,  $argCount)
        . implode('', $argstrings);

      //$hs1 = sprintf('%08X', strlen($hs1)) . $hs1;
      $hs1 = safe_tostring_(STSFMT_LONG, strlen($hs1)) . $hs1;

      $result = $this->safe_write($hs1, strlen($hs1));
      if ($result === false)
      {
        $this->needReconnection('safe_write() failed');
      }

      PF_RET();
      return $result;
    }

    function getAnswer(&$commandId, &$objectType, &$objectId, &$retList) //!< @private
    {
    	PF_CALL();
      if (!$this->tryconnect())
      {
        $this->log_error("getAnswer(): Can't connect");
        PF_RET();
        return false;
      };

      $this->currentChunkSize = 0;

      if ($this->buffered_read($hs1, 32) !== 0)
      {
        $this->needReconnection("getAnswer(): Failed to read chunk header.");
        PF_RET();
        return false;
      };

      list($sequenceId, $commandId, $objectType, $objectId, $argCount) = sscanf($hs1, "%08X%04X%08X%08X%04X");
      if (!$commandId || (($objectType == 0) && ($commandId != CMD_GETOBJLIST)))
      {
        PF_RET();
        return false;
      };

      $retList = array();
      while ($argCount > 0)
      {
        if ($this->buffered_read($hs1, 8 + 4 + 8) !== 0)
        {
          $this->needReconnection("getAnswer(): Failed to read argument header.");
	        PF_RET();
          return false;
        };
        list($argId, $argType, $argLength) = sscanf($hs1, "%08X%04X%08X");
        if ($this->buffered_read($arg, $argLength) !== 0)
        {
          $this->needReconnection("getAnswer(): Failed to read argument data.");
	        PF_RET();
          return false;
        };


        //$this->message("Next result: ".$hs1.$arg);
        switch ($argType)
        {
          case AT_INVALID:
          case AT_INT:
          case AT_BOOL:
            $arg = intval($arg, 16);
            break;
          case AT_FLOAT:
            $arg = floatval($arg);
            break;
          case AT_PTR:
            $hs1 = "";
            for ($hi1 = 0; $hi1 < $argLength; $hi1+=2)
            {
              $hs1 .= chr(intval(substr($arg, $hi1, 2), 16));
            };
            $arg = $hs1;
            break;
          case AT_STRING:
          case AT_OBJECT:
          default:
            $arg = strval($arg);
            break;
        };
        $retList[] = array($argId, $argType, $argLength, $arg);
        $argCount--;
      };
      $this->chunkSize = 0;
      PF_RET();
      return true;
    }

    function is_object_(&$object, $checkid = true)  //!< @private
    {
      // Check type and id fields without calling them -- or it will kill script on error.
      return $object && is_object($object) && $object->type() && (!$checkid || $object->id());
    }

    function log_error($string = "") //!< @private
    {
      $messageLine = sprintf("%d - %d - %s", time(), $this->myPID, $string);
      logfile(PATH_LOGS.'wbds_errors.log',$messageLine);
    }

    function message($string = "") //!< @private
    {
      if (defined("WBDS_DEBUG") && (WBDS_DEBUG >= 1))
      {
        $messageLine = sprintf("%d - %d - %s", time(), $this->myPID, $string);
        if (WBDS_DEBUG & 0x01)
        {
          error_log($messageLine);
        };
        if (WBDS_DEBUG & 0x02)
        {
          echo $messageLine."\n";
        };
      }
    }

    function debug($string = "") //!< @private
    {
      if (defined("WBDS_DEBUG") && (WBDS_DEBUG & 0x04))
      {
        error_log($this->myPID." - ".$string);
      };
    }

    //
    //  Private functions (unused?)
    //

    function safe_read(&$buffer, $size = 0) //!< @private NOTE: Seems to be unused.
    {
      $buffer = "";
      if ($this->bad() || ($size < 0))
      {
        $this->log_error(__FILE__.":".__LINE__." - invalid status");
        return -1;
      };
      while (($size > 0) && !feof($this->socket()))
      {
        $hs1 = fread($this->socket(), min(1024, $size));
        $hi1 = strlen($hs1);
        $buffer .= $hs1;
        $size-= $hi1;
      };
      return $size;
    }

    //  ====================================================================================================
    //  Public interface
    //

    function WBDS($serverPool = false)
    {
      global $PHP_SELF;
      $this->myPID = getmypid();
      $this->socket(0);
      $this->status(WBDS_DISC);
      $this->serverURL = false;
      $this->sequenceId = 0;
      $this->chunkSize = 0;
      $this->serverPool = ($serverPool) ? $serverPool : array();
      $this->numReconnections = 0;
    }

    function is_connected() { return $this->status() == WBDS_CONN; }
    function is_disconnected() { return $this->status() == WBDS_DISC; }
    function is_failed() { return $this->status() == WBDS_FAIL; }

    function bad() { return !$this->is_connected(); }
    function good() { return !$this->bad(); }

    function downloadObj(&$object)  //!< @public
    {
      if (!$this->is_object_($object))
      {
        $this->log_error("downloadObj(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_CHECKOBJ, $object->type(), $object->id(), array()) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          (!is_array($retList)) ||
          (sizeof($retList) != 1))
      {
        $this->log_error("downloadObj(): Exchange sequence broken");
        return false;
      };
      list($argId, $argType, $argLength, $arg) = reset($retList);
      if (($argType != AT_INT) || (intval($arg) != $object->id()))
      {
        return false;
      };
      $argList = array_keys($object->propList);
      if (($this->sendCmd($object, CMD_GETOBJPROP, $object->type(), $object->id(), $argList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        $this->log_error("downloadObj(): Can't download object properties");
        return false;
      };
      $setProps = array();
      $unsetProps = array();
      foreach ($retList as $key => $retRow)
      {
        list($argId, $argType, $argLength, $arg) = $retRow;
        if ($argType != AT_INVALID)
        {
          $setProps[$argId] = $arg;
        }
        else
        {
          $unsetProps[$argId] = $arg;
        };
      };
      $object->set($setProps);
      $object->del($unsetProps);
      $object->is_dirty(false);
      return true;
    }

    function uploadObj(&$object)  //!< @public
    {
      if (!$this->is_object_($object, false))
      {
        $this->log_error("uploadObj(): Invalid arguments");
        return false;
      };

      $argList = array();
      $objString = serialize($object);
      $object->set(OBJPROP_CONTENTS, $objString);
      foreach ($object->propList as $key=>$value)
      {
        $argList[] = array( $key,
          isset($object->propValues[$key]) ? $object->propValues[$key] : false,
          isset($object->propValues[$key]));
      };

	  $b1 = $this->sendCmd($object, CMD_UPLOADOBJ, $object->type(), $object->id(), $argList);
	  if ($b1) $b2 = $this->getAnswer($commandId, $objectType, $objectId, $retList);
	  if ($b1 && $b2) $b3 = sizeof($retList);

      if (!$b1 || !$b2 || !$b3) {
        $this->log_error(sprintf("uploadObj(): Send/receive cycle is broken (%d, %d, %d) b1:(%s, %s, %s, %s, %s) b2:(%s, %s, %s, %s) ",
			$b1, $b2, $b3,
			get_class($object), CMD_UPLOADOBJ, $object->type(), $object->id(), serialize($argList),
			$commandId, $objectType, $objectId, serialize($retList)
			));
        return false;
      };
      if ($object->id() == 0)
      {
        list($argId, $argType, $argLength, $arg) = reset($retList);
        if ($arg > 0)
        {
          $object->id($arg);
        };
      };
      $object->is_dirty(false);
      return $object->id() > 0;
    }

    function checkObj(&$object) //!< @public
    {
      if (!$this->is_object_($object))
      {
      	// везде где вызывается потом пытается сделать uploadObj и если не удалось тогда уже пишет в лог
        //$this->log_error("checkObj(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_CHECKOBJ, $object->type(), $object->id(), array()) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          (!is_array($retList)) ||
          (sizeof($retList) != 1))
      {
        return false;
      };
      list($argId, $argType, $argLength, $arg) = reset($retList);
      if (($argType != AT_INT) || ($arg != $object->id()))
      {
        return false;
      };
      return true;
    }

    function delObj(&$object) //!< @public
    {
      if (!$this->is_object_($object))
      {
        $this->log_error("delObj(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_DESTROYOBJ, $object->type(), $object->id(), array()) == false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        return false;
      };
      if (($objectType != $object->type()) || ($objectId != $object->id()))
      {
        return false;
      };
      return true;
    }

    function getObjLink(&$object, $linkList = false)  //!< @public
    {
      if (!$this->is_object_($object) || !is_array($linkList) || !sizeof($linkList))
      {
        $this->log_error("getObjLink(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_GETOBJLINK, $object->type(), $object->id(), $linkList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          (!is_array($retList)) ||
          (sizeof($retList) == 0))
      {
        return false;
      };
      if (is_array($retList) && (sizeof($retList) > 0))
      {
        foreach ($retList as $retRow)
        {
          list($argId, $argType, $argLength, $arg) = $retRow;
          $pairs = explode(";", $arg);
          foreach ($pairs as $pair)
          {
            if ($pair != "")
            {
              list($objType, $objId) = sscanf($pair, "%08X:%08X");
              $object->addLink($objType, $objId);
            };
          };
        };
      };
      return true;
    }

    function addObjLink(&$object, $linkList = false)  //!< @public
    {
      if (!$this->is_object_($object) || !is_array($linkList) || !sizeof($linkList))
      {
        $this->log_error("addObjLink(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_ADDOBJLINK, $object->type(), $object->id(), $linkList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          (!is_array($retList)) ||
          (sizeof($retList) == 0))
      {
        return false;
      };
      return true;
    }

    function &getObjList($objList = false)  //!< @public
    {
      $falseret = false;

      if (!$objList || !is_array($objList) || !sizeof($objList))
      {
        return $falseret;
      };
      if (($this->sendCmd(false, CMD_GETOBJLIST, 0, 0, $objList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          !is_array($retList) ||
          (sizeof($retList) == 0))
      {
        return $falseret;
      };
      $objects = array();
      foreach ($retList as $retRow)
      {
        list($argId, $argType, $argLength, $arg) = $retRow;
        $newObj = unserialize($arg);
        if ($newObj && is_object($newObj))
        {
          $newObj->id($argId);
          $objects[] = $newObj;
        };
      };
      return $objects;
    }

    //
    //  Unused public interface?
    //

    function delObjLink(&$object, $linkList = false)  //!< @public Seems to be unused
    {
      if (!$this->is_object_($object) || !is_array($linkList) || !sizeof($linkList))
      {
        $this->log_error("delObjLink(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_DELOBJLINK, $object->type(), $object->id(), $linkList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          (!is_array($retList)) ||
          (sizeof($retList) == 0))
      {
        return false;
      };
      return true;
    }

    function setProps(&$object, $propList = false)  //!< @public Seems to be unused
    {
      if (!$this->is_object_($object) || !$propList || !is_array($propList) || !sizeof($propList))
      {
        $this->log_error("setProps(): Invalid arguments");
        return false;
      };
      $propValues = array();
      foreach ($propList as $propId)
      {
        if ($object->propType($propId) != false)
        {
          $propValues[$propId] = &$object->propValues[$propId];
        };
      };

      if (($this->sendCmd($object, CMD_SETOBJPROP, $object->type(), $object->id(), $propValues) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          !is_array($retList) ||
          (sizeof($retList) == 0))
      {
        return false;
      };
      return true;
    }

    function getProps(&$object, $propList = false)  //!< @public Seems to be unused
    {
      if (!$this->is_object_($object) || !$propList || !is_array($propList) || !sizeof($propList))
      {
        $this->log_error("getProps(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_GETOBJPROP, $object->type(), $object->id(), $propList) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false) ||
          !is_array($retList) ||
          (sizeof($retList) == 0))
      {
        return false;
      };
      $setProps = array();
      $unsetProps = array();
      foreach ($retList as $key => $retRow)
      {
        list($argId, $argType, $argLength, $arg) = $retRow;
        if ($argType != AT_INVALID)
        {
          $setProps[$argId] = $arg;
        }
        else
        {
          $unsetProps[$argId] = $arg;
        };
      };
      $object->set($setProps);
      $object->del($unsetProps);
      return true;
    }

    function dumpObj(&$object)  //!< @public Seems to be unused.
    {
      if (!$this->is_object_($object))
      {
        $this->log_error("dumpObj(): Invalid arguments");
        return false;
      };
      if (($this->sendCmd($object, CMD_DUMPOBJ, $object->type(), $object->id(), array()) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        return false;
      };
      return true;
    }

    function dumpAll()  //!< @public Seems to be unused
    {
      if (($this->sendCmd(false, CMD_DUMPALL, false, false, array()) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        return false;
      };
      return true;
    }

    function shutdownServer() //!< @public Seems to be unused
    {
      if (($this->sendCmd(false, CMD_SHUTDOWN, false, false, array()) === false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        return false;
      };
      return true;
    }

    function setObjTProps($objectType, $propAmount = 0, $idleTime = 0, $queueLength = 0, $delLinks = 0 )  //!< @public Seems to be unused
    {
      if (($this->sendCmd(false, CMD_SETOBJTPROPS, $objectType, false, array($propAmount, $idleTime, $queueLength, $delLinks)) == false) ||
          ($this->getAnswer($commandId, $objectType, $objectId, $retList) == false))
      {
        return false;
      };

      return true;
    }
  };
?>