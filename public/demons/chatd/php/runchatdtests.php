<? /* $Id: runchatdtests.php,v 1.5 2006/09/27 07:28:51 agladysh Exp $ */
$runtime = microtime(true);
include("chatd_test.php");
// above set $suite to self-test suite

$title = 'Chatd test run results';
?>
<html>
  <head>
    <title><?php echo $title; ?></title>
    <STYLE TYPE="text/css">
      <?php
    include ("stylesheet.css");
?>
    </STYLE>
  </head>
  <body>
    <h1><?php echo $title; flush(); ?></h1>
      <?php
       if (isset($only))
  $suite = new TestSuite($only);
  $result = new PrettyTestResult();

  $suite->run($result);
  print('</table>');
  $result->report();
  $runtime = microtime(true) - $runtime;
  print("<hr>This report is generated at " . date("l dS of F Y h:i:s A") . ". Execution time $runtime seconds");
  ?>
  </body>
</html>
