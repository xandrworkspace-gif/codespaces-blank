<?php

include_once "phpunit.php";

/**
 * Extends TestResult to report test progress and results
 * to a listener through a socket.
 * sends reports in XML (see class Report)
 *
 * http://phpunit.sourceforge.net/
 */


class SocketTestResult extends TestResult {

//TODO: set max execution time to avoid timeouts in the middle of test executions.
	
	var $listener; //TODO: have an array of listeners.
	var $reportFactory;
	var $isFirstTest = true;

	/**
	 *
	 *
	 */		
	function SocketTestResult() {
		
		$this->listener = new ListenerProxy("localhost", 12345);
		$this->listener->establishConnection();
		
		$this->reportFactory = new ReportFactory();
		
		$this->TestResult();  // call superclass constructor
	}
	
	function setSuite(&$testSuite) {
	
		parent::setSuite(&$testSuite);
		
		// send new report: new test suite started with the number of test cases it contains
		// the listener will keep track of the number of tests executed to determine the end of test suite.
		$this->notify($this->reportFactory->createTestSuiteStartedReport(get_class($testSuite), get_class($testSuite), $testSuite->countTestCases()));	
		
	}
	
	/**
	 * notify the network listener(s) that there the 
	 * given test has started. 
	 * the listener can keep track of the test progress
	 */
	function _startTest($test) {
		
		if($this->isFirstTest && $this->suite != null) {
			
			$this->isFirstTest = false;
			echo "<i>testing started</i> <br><br>";
			$this->notify($this->reportFactory->createTestingStartedReport($this->countAllTestsInSuite()));
		}
		
		$this->notify($this->reportFactory->
						createStartTestReport($this->getTestID($test), $this->getTestName($test), $this->getParentTestSuiteName($test)));
		
	} // end of function start test

// will replace this with _endTest as it does the job fine
//	/**
//	 * overriding the parent's implementation.
//	 * adding the listerner's notification feature.
//	 * the goal is to let the listern know of the failure as soon as it happens.
//	 */
//	
//	function addFailure($test) {
//				
//		// run parent implementation
//		parent::addFailure($test);
//		
//		// notify listeners that there has been a failure.
//		$this->notify("test_failed ".$this->getTestName($test));
//		
//	} // end of function end failure

	/**
	 * notify the network listener(s) that there the 
	 * given test has ended giving the outcome.
	 * the listener can keep track of the test progress
	 */	
	function _endTest($test) {
		
		$this->notify($this->reportFactory->createEndTestReport($this->getTestID($test), $this->getTestVerdict($test)));
		
		
	} // end of function end test
	
	
	/**
	 * Generate final summary.
	 * called after the test suite completes.
	 * notifies listener(s) of the end, and generates html report.
	 */			
	function report() {

		$this->notify($this->reportFactory->createEndAllTestsReport());
	}	

	
	/**
	 * notify registered listeners of this message.
	 * messages respect a format...
	 *
	 * example:
	 * test1 fail "expected 2 found 1"
	 *
	 */
	function notify($report) {
		
		// TODO: should I have a persistent connection or reconnect for every notification?
		// I will make it persistent, no overhead.
		// most tests will execute fast.
		// even better, assume persistent, but if connection closed, attempt to reconnect.	
		// made up my mind: persistent!
		
		$this->listener->sendMessage($report->getXML());
	
	}

	/**
	 *
	 *
	 */		
	function getTestID($test) {
		return $this->getParentTestSuiteName($test).".".$this->getTestName($test);
	}


	function getParentTestSuiteName($test) {
	
		return get_class($test);
		
	}


	function getTestName($test) {
		return $test->name();
	}


	/**
	 *
	 *
	 */		
	function getTestVerdict($test) {
		
		// format of verdict: "(pass|fail|error) moreInfo"
		// moreInfo can be empty for pass. 
		// It is the reason why the test failed or errored (the exceptions that occured)
		// moreInfo takes the following form: <info> salam agadir 1 <info> salam agadir 2 <info>....
		
		$verdict = "";
		$moreInfo = "<exceptions>";
		
		//flags:
		$testFailed = $test->failed();
		$testErrored = $test->errored();
		$testPassed = !$test->failed();
		
		//a failed test that was expected to fail is actually a pass.
		$metOutcome = ""; 
		
		
		//determine exceptions if any: inspired by TextResult
		if($testFailed || $testErrored) {
		    $exceptions = $test->getExceptions();
		    while (list($na, $e) = each($exceptions)) {
				
				//TODO: make sure e_msg will not cause exceptions on the handler's side.
				//i.e: that it is valid XML.
				
				$e_msg = $e->getMessage();
				//$e_msg = "exception data, not available in this version of the plugin";
				
				//remove the < and > signs. parser complains.
				$e_msg = ereg_replace("<", "&lt;", $e_msg);
				$e_msg = ereg_replace(">", "&gt;", $e_msg);
				$e_msg = ereg_replace("&nbsp;", " ", $e_msg);
				
				$moreInfo .= "<exception desc='".$e_msg."' />";
				
		    }				
		}
		
		$moreInfo .= "</exceptions>";
		
		$verdict = "<verdict desc='";
		//put strings together for final verdict.
		if ($testErrored) {
			$verdict .= "errored'>"." ".$moreInfo."</verdict>";	
		} else if ($testFailed) {
			$verdict .= "failed'>"." ".$moreInfo;				
		} else if ($testPassed) {
			$verdict .= "passed'>"." ".$moreInfo;	
		} else {
			$verdict .= "unknown'>"." ".$moreInfo;	
		}
		
		$verdict .= "</verdict>";
		
		return $verdict;				
	}
	
} // end of class SocketTestResult


class ListenerProxy {

	var $host;
	var $port;
	var $sock; // the persistent connection to the listener.
	var $connected;
	var $connection;

	/**
	 *
	 *
	 */		
	function ListenerProxy($h, $p) {
		
		$this->host = $h;
		$this->port = $p;	
		$this->connected = false;
		$this->sock = -1;
	}	

	/**
	 *
	 *
	 */		
	function establishConnection() {
		
		$this->sock = fsockopen($this->host, $this->port);
		$this->connected = $this->sock != false;
	}
	
	
	/**
	 * forward this message to the listener through established connection
	 */

	function sendMessage($msg) {

		//to see messages on browser for debugging
		$msg2 = ereg_replace("<", "&lt;", $msg);
		$msg2 = ereg_replace(">", "&gt;", $msg2);
	
		echo "will send: <br><br> $msg2 <br><br>";
		
		
		
		if ($this->connected) {
			
			//output msg and force a newline to mark end of current report.
			fputs($this->sock, $msg);
			fputs($this->sock, "\n");	
			
		} else {
			//echo "ERROR: cannot send because connection is closed. <br>";	
		}
				
	}
	
}

/**
 *
 *
 */
	
/* <report 
 *		testCount=$ctr
 *		command = "testStarted|testFinished|endAll" 
 *		testID="$testSuite_$testCaseName"
 * 
 *		testVerdict="(pass|fail|error)">
 * 		<exceptions>
 *			<exception description="$exception->getMessage()"/>
 *		</exceptions>	
 *		 
 * </report>
 */
 
define("TESTING_STARTED", "startAll");
define("TEST_SUITE_STARTED", "testSuiteStarted"); 
define("TEST_STARTED", "testStarted");
define("TEST_ENDED", "testFINISHED");
define("END_ALL", "endAll");

class Report {
		
	var $command;
	var $testCount;
	var $testID;
	var $verdict;
	var $reportID;
	var $testName;
	var $parentTestSuiteName;

	
	/**
	 *
	 *
	 */		
	function Report($id) {
		$this->reportID = $id;	
	}
	


	/**
	 *
	 *
	 */		
	function getXML() {
		
		$xml = "";
		
		$xml .= "<report id='$this->reportID' ".
				"command='$this->command' ". 
				"testCount='$this->testCount' ".
				"testID='$this->testID' ".
				"testName='$this->testName' ".
				"parentTestSuiteName='$this->parentTestSuiteName' ".
				">".
				$this->verdict.						
				"</report> ";				
 		
		return $xml;
		
	}



	/**
	 *
	 *
	 */		
	function setCommand($cmd) {
		$this->command = $cmd;	
	}


	
	/**
	 *
	 *
	 */		
	function setTestCount($ctr) {
		$this->testCount = $ctr;	
	}


	/**
	 *
	 *
	 */		
	function setTestID($id) {
		$this->testID = $id;	
	}


	/**
	 *
	 *
	 */		
	function setVerdict($verdict) {

		$this->verdict = $verdict;	
	}

	/**
	 *
	 *
	 */		
	function setTestName($testName) {

		$this->testName = $testName;	
	}


	/**
	 *
	 *
	 */		
	function setParentTestSuiteName($parentTestSuiteName) {

		$this->parentTestSuiteName = $parentTestSuiteName;	
	}
	
	
	
}

class ReportFactory {

	var $reportCount; // the id for every report sent.
	var $testCount; // the count for a test. the start and end reports of the same test have the same count.


	/**
	 *
	 *
	 */		
	function ReportFactory() {
				
		$this->reportCount = 0;
		$this->testCount = 0;
							
	} // end of constructor

	function createTestSuiteStartedReport($testSuiteName, $testSuiteID, $numTests) {

		$report = new Report($this->getNewReportCount());
		
		$report->setCommand(TEST_SUITE_STARTED);
		$report->setTestCount($numTests);
		$report->setTestID($testSuiteName);
		
		return $report;			
		
		
	}
	
	function createTestingStartedReport($numTestsOverall) {
	
		$report = new Report($this->getNewReportCount());
		
		$report->setCommand(TESTING_STARTED);
		$report->setTestCount($numTestsOverall); // passing the number of tests as the test count.
		$report->setTestID(-1);
		
		return $report;		
	}

	/**
	 *
	 *
	 */	
	function createStartTestReport($testID, $testName, $parentTestSuiteName) {
		
		$report = new Report($this->getNewReportCount());
		
		$report->setCommand(TEST_STARTED);
		$report->setTestCount($this->getNewTestCount());
		$report->setTestID($testID);
		$report->setTestName($testName);
		$report->setParentTestSuiteName($parentTestSuiteName);
		
		return $report;	
	}

	/**
	 *
	 *
	 */		
	function createEndTestReport($testID, $verdict) {
		
		$report = new Report($this->getNewReportCount());
		
		$report->setCommand(TEST_ENDED);
		$report->setTestCount($this->testCount);
		$report->setTestID($testID);
		
		$report->setVerdict($verdict);
		
		return $report;	
	}	

	/**
	 *
	 *
	 */	
	function createEndAllTestsReport() {
		
		$report = new Report($this->getNewReportCount());
		
		$report->setCommand(END_ALL);
		$report->setTestCount(-1);
		$report->setTestID(-1);		
		
		return $report;	
	}	


	/**
	 *
	 *
	 */		
	function getNewReportCount() {
		return ++$this->reportCount;
	}


	/**
	 *
	 *
	 */		
	function getNewTestCount() {
		return ++$this->testCount;	
	}
	
} // end of class ReportFactory



?>