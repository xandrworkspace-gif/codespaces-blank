<html><head><title>Raw Load Test</title></head><body bgcolor="#ffffff">
<? /* $Id: raw_load_test.php,v 1.11 2006/10/02 07:32:23 agladysh Exp $ */

//  TODO: Hack! To make it visible in assertion handler.
global $run_time;
$run_time = microtime(true);
$iterations = 3000;

class Perfcounter
{
  var $results;

  function Perfcounter()
  {
    $this->results = array();
  }

  function start_measure($name = false)
  {
    return microtime(true);
  }

  function set_measure($name = false, $dt = false)
  {
    if (is_string($name) && is_float($dt))
    {
      if (isset($this->results[$name]))
      {
        $this->results[$name][0]++;
        $this->results[$name][1][] = $dt;
      }
      else
      {
        $this->results[$name] = array(1, array($dt));
      }
    }
  }

  function stop_measure($name = false, $starttime = false)
  {
    if (is_string($name) && is_float($starttime))
    {
      $dt = microtime(true) - $starttime;
      if (isset($this->results[$name]))
      {
        $this->results[$name][0]++;
        $this->results[$name][1][] = $dt;
      }
      else
      {
        $this->results[$name] = array(1, array($dt));
      }
    }
  }

  function dump()
  {
    $sz = sizeof($this->results);
    print("<br><h1>Total $sz parameters measured</h1><table border=1><tr><th>Name</th><th>Average (sec)</th><th>Times</th><th>Total (sec)</th></tr>");
    foreach ($this->results as $name => $timing)
    {
      $times = $timing[0];
      $total = array_sum($timing[1]);
      $avg = $total / $times;
      print("<tr><td>$name</td><td>$avg</td><td>$times</td><td>$total</td></tr>\n");
    }
    print("</table>");
  }

  function dump_ticks($name = false)
  {
    print("<br><h1>Dumping ticks for $name</h1><table>");
    $i = 1;
    foreach ($this->results[$name][1] as $time)
    {
      print("<tr><td>$name</td><td>$i</td><td>$time</td></tr>");
      $i++;
    }
    print("</table>");
  }

  function dump_all_ticks()
  {
    print("<br><h1>Dumping ticks</h1><table>\n");
    foreach ($this->results as $name => $timing)
    {
      print("<tr><td>$name</td>");
      foreach ($timing[1] as $time)
      {
        print("<td>$time</td>");
      }
      print("</tr>\n");
    }
    print("</table>");
  }
}

global $perfcounter;
$perfcounter = new Perfcounter();


print("Testing with $iterations iterations...<br>"); flush();
error_log("**** Started testing with $iterations iterations");

require_once("test/phpunit.php");

require_once("include/config.inc");
require_once("lib/chat.lib");

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

function my_assert_handler($file, $line, $code)
{
  global $run_time;
  $micro = microtime(true);
  $runtime2 = $micro - $run_time;
  $msg = "Assertion failed at $file:$line after $runtime2 seconds of execution ($code)";
  error_log($msg);
  print("<font color=\"#ff0000\"><b>$msg</b></font><br>");
  flush();
}

assert_options(ASSERT_CALLBACK, 'my_assert_handler');

global $num_total_run, $num_succeeded, $num_failed;
$num_total_run = 0;
$num_succeeded = 0;
$num_failed = 0;

function testChatlibBasic()
{
  global $num_total_run, $num_succeeded, $num_failed;
  $num_total_run++;

  $texttag = 'texttag';
  $teststring = "msg_$num_total_run";
  $user_id = 100;
  $area_id = 200;

  $channel_data = array(
    'user_id' => $user_id,
    'area_id' => $area_id,
  );

  $msg_id = chat_msg_send(array($texttag => $teststring), CHAT_CHF_USER, $channel_data);
  if (!$msg_id)
  {
    $num_failed++;
    $errmsg = "#$num_total_run: Failed to send posted message";
    error_log($errmsg);
    print("<font color=\"#ff0000\"><b>$errmsg</b></font><br>");
    flush();
    return false;
  }

  $messages = chat_msg_list(CHAT_CHF_USER, $channel_data);
  $nummessages = sizeof($messages);
  assert(is_array($messages));
  if ($nummessages > 0)
  {
    $last_msg = NULL;
    //  Find our message:
    foreach ($messages as $msg)
    {
      if ($msg['id'] == $msg_id)
      {
        $last_msg = $msg;
        break;
      }
    }

    if ($last_msg == NULL)
    {
      print("<font color=\"#ff8800\"><b>#$num_total_run: Warning, message $msg_id not found</b></font><br>");
    }
    else
    {
      assert($teststring == $last_msg[$texttag]);

      $new_channel_data = $last_msg['channel_data'];

      assert(is_array($new_channel_data));
      assert($user_id == $new_channel_data['user_id']);
      assert($area_id == $new_channel_data['area_id']);
    }

    assert(chat_msg_delete($msg_id));
  }
  else
  {
    $num_failed++;
    $errmsg = "#$num_total_run: Failed to get posted message";
    error_log($errmsg);
    print("<font color=\"#ff0000\"><b>$errmsg</b></font><br>");
    flush();
    return false;
  }

  $num_succeeded++;
  return true;
}

$looptime = microtime(true);
$reportinterval = 25;

for ($i = 1; $i <= $iterations; ++$i)
{
  testChatlibBasic();
  if ($i % $reportinterval == 0)
  {
    $curtime = microtime(true);
    $dt = $curtime - $looptime;
    $onetime = $dt / $reportinterval;
    $looptime = $curtime;
    print("#$i : $dt ($onetime)<br>\n");
    flush();
  }
}

global $run_time;
print("<h1>Done</h1>Total: $num_total_run, succeeded: $num_succeeded, failed: $num_failed");
$perfcounter->dump();

//$perfcounter->dump_ticks("fread");
//$perfcounter->dump_all_ticks();

$runtime3 = microtime(true) - $run_time;
print("<hr>This report is generated at " . date("l dS of F Y h:i:s A") . ". Execution time $runtime3 seconds<br>");
error_log("**** Done testing");
?>
</body></html>
