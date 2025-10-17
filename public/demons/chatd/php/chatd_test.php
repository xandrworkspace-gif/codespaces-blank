<? /* $Id: chatd_test.php,v 1.6 2006/10/02 12:10:11 agladysh Exp $ */

require_once("test/phpunit.php");

require_once("include/config.inc");
require_once("include/wbds.inc");

//  TODO: To test:
//  1. Raw chatd interface
//  2. wbds.inc
//  3. chatd.lib
//  4. object.inc
//  5. objects.inc?

class basic_wbds_test extends TestCase
{
  var $mWBDS;

  function basic_wbds_test($name = "basic_wbds_test") { $this->TestCase($name); }

  function setUp()
  {
    $this->mWBDS = new WBDS(explode(",", WBDS_SERVERS));
  }

  function tearDown()
  {
    $this->mWBDS = NULL;
  }

  //  Looks like pool() is a private function though...
  function testServerPool() //  TODO: Test pool setting, test how it works with empty and incorrect pools (change pools in runtime, after connect).
  {
    $pool = explode(",", WBDS_SERVERS);
    $poolsz = sizeof($pool);
    $this->assert($poolsz > 0, "Invalid WBDS_SERVERS (empty): " . WBDS_SERVERS);

    $wbdspool = $this->mWBDS->pool();

    $this->assert(is_array($wbdspool), "Invalid WBDS pool type, array expected, got: " . gettype($wbdspool));
    $this->assertEquals(sizeof($wbdspool), $poolsz, "Invalid WBDS pool size");
    for ($i = 0; $i < $poolsz; ++$i)
    {
      $this->assertEquals($pool[$i], $wbdspool[$i], "Invalid WBDS pool entry (does not match template)");
    }
  }

  function testEmptyConnect()
  {
    $this->assert(!$this->mWBDS->connect());
  }

  function testBadConnect()
  {
    $this->assert(!$this->mWBDS->connect(explode(",", WBDS_SERVERS))); //  Needs string, not an array
  }

  function testUnexpectedDisconnect()
  {
    $this->assert(!$this->mWBDS->disconnect());
  }

  function testConnect()  //  TODO: Test behaviour on double calls to connect.
  {
    $wbdspool = $this->mWBDS->pool();
    $this->assert($this->mWBDS->connect($wbdspool[0]));
    $this->assert($this->mWBDS->disconnect());
  }

  function testTryConnect()
  {
    $this->assert($this->mWBDS->tryconnect());
    $this->assert($this->mWBDS->disconnect());
  }

  //function testTimeout()  //  TODO: Implement this somehow.
  //{
  //}

  //  TODO: Test safe_read(), safe_write().
  //  TODO: Test sendCmd() / getAnswer().
}

class DummyClass { }

class DummyChatdObj extends CDObject
{
  function DummyChatdObj($type = OBJTYPE_MSG, $id = false)  //  TODO: false?!
  {
    parent::CDObject($id);
    parent::type($type);
    $this->propList = array(
      OBJPROP_CONTENTS => AT_STRING,
    );
    $this->propFields = array(
      OBJPROP_CONTENTS => "contents",
    );
  }
}

class objects_wbds_test extends TestCase
{
  var $mWBDS;

  function objects_wbds_test($name = "objects_wbds_test") { $this->TestCase($name); }

  function setUp()
  {
    $this->mWBDS = new WBDS(explode(",", WBDS_SERVERS));
  }

  function tearDown()
  {
    $this->mWBDS = NULL;
  }

  function testBadCheckObj()
  {
    $wbds = $this->mWBDS;

    $notanobject = array();
    $this->assert(!$wbds->checkObj($notanobject));

    //  TODO: Make this work without fatal errors.
    //$objectbadiface = new DummyClass();
    //$this->assert(!$wbds->checkObj($objectbadiface));
  }

  function testBadUploadObj()
  {
    $wbds = $this->mWBDS;

    $notanobject = array();
    $this->assert(!$wbds->uploadObj($notanobject));

    //  TODO: Make this work without fatal errors.
    //$objectbadiface = new DummyClass();
    //$this->assert(!$wbds->checkObj($objectbadiface));
  }

/*///  TODO: Make this work or at least verify double object deletion does not break anything!
  //  Note: Is GC-nature somehow related to this?
  function testDoubleDeletion()
  {
    $wbds = $this->mWBDS;
    $testobj = new DummyChatdObj();
    $this->assert(!$wbds->checkObj($testobj), "No such object yet");
    $this->assert($wbds->uploadObj($testobj), "Failed to upload object");
    $this->assert($wbds->checkObj($testobj), "Object should be uploaded now");
    $this->assert($wbds->delObj($testobj), "Deleting object");
    $this->assert(!$wbds->delObj($testobj), "Double deletion should fail");
  }
/**/

  function testUploadAndDelete()  //  TODO: This is duplicated below. Remove or split.
  {
    $wbds = $this->mWBDS;
    $testobj = new DummyChatdObj();
    $this->assert(!$wbds->checkObj($testobj), "No such object yet");
    $this->assert($wbds->uploadObj($testobj), "Failed to upload object");
    $this->assert($wbds->checkObj($testobj), "Object should be uploaded now");
    $this->assert($wbds->delObj($testobj), "Deleting object");
    $this->assert(!$wbds->checkObj($testobj), "Object is deleted, checkObj() should fail");
  }

  function testBadDownloadObj()
  {
    $wbds = $this->mWBDS;

    $notanobject = array();
    $this->assert(!$wbds->downloadObj($notanobject), "Downloading non-object");

    $testobj = new DummyChatdObj();
    $this->assert(!$wbds->downloadObj($testobj), "Downloading non-uploaded object");
  }

  function testDownloadObj()
  {
    $wbds = $this->mWBDS;
    $testobj = new DummyChatdObj();
    $this->assert(!$wbds->checkObj($testobj), "No such object yet");
    $this->assert($wbds->uploadObj($testobj), "Failed to upload object");
    $this->assert($wbds->checkObj($testobj), "Object should be uploaded now");

    //  TODO: What else to test here? Should something change in testobj?
    $this->assert($wbds->downloadObj($testobj), "Downloading object");

    $this->assert($wbds->delObj($testobj), "Deleting object");
    $this->assert(!$wbds->checkObj($testobj), "Object is deleted, checkObj() should fail");
  }
}

require_once("lib/chat.lib");

class chatlib_test extends TestCase
{
  function chatlib_test($name = "chatlib_test") { $this->TestCase($name); }

  function testChatlibBasic()
  {
    $texttag = 'texttag';
    $teststring = 'ABCDEF!';
    $user_id = 100;
    $area_id = 200;

    $channel_data = array(
      'user_id' => $user_id,
      'area_id' => $area_id,
    );

    $msg_id = chat_msg_send(array($texttag => $teststring), CHAT_CHF_USER, $channel_data);
    $this->assert(is_integer($msg_id));

    $messages = chat_msg_list(CHAT_CHF_USER, $channel_data);
    $nummessages = sizeof($messages);
    $this->assert(is_array($messages), "Messages are not array");
    $this->assert($nummessages > 0, "Got no messages");

    if ($nummessages > 0)
    {
      $last_msg = $messages[$nummessages - 1];

      $this->assertEquals($msg_id, $last_msg['id']);
      $this->assertEquals($teststring, $last_msg[$texttag]);

      $new_channel_data = $last_msg['channel_data'];

      $this->assert(is_array($new_channel_data));
      $this->assertEquals($user_id, $new_channel_data['user_id']);
      $this->assertEquals($area_id, $new_channel_data['area_id']);

      $this->assert(chat_msg_delete($msg_id));
    }
    else
    {
      $this->fail("Got no messages, failing");
    }
  }
}

class chatlib_load_test extends TestCase
{
  function chatlib_test($name = "chatlib_test") { $this->TestCase($name); }

  function testChatlibBasicLoad()
  {
    $tester = new chatlib_test();
    for ($i = 0; $i < 3000; ++$i)
    {
      $tester->testChatlibBasic();
    }
  }
}

/*
class load_test extends TestCase
{
  var $mServers;

  function load_test($name = "load_test") { $this->TestCase($name); }

  function setUp()
  {
    $this->mServers = explode(",", WBDS_SERVERS);
  }

  function tearDown()
  {
    $this->mServers = NULL;
  }

  function testHighLoad()
  {
    for ($i = 0; $i < 3000; ++$i)
    {
      $wbds = new WBDS($this->mServers);
      $testobj = new DummyChatdObj();
      $this->assert(!$wbds->checkObj($testobj), "No such object yet");
      $this->assert($wbds->uploadObj($testobj), "Failed to upload object");
      $this->assert($wbds->checkObj($testobj), "Object should be uploaded now");
      $this->assert($wbds->downloadObj($testobj), "Downloading object");
      $this->assert($wbds->delObj($testobj), "Deleting object");
      $this->assert(!$wbds->checkObj($testobj), "Object is deleted, checkObj() should fail");
    }
  }
}
*/

$suite = new TestSuite();
$suite->addTest(new TestSuite("basic_wbds_test"));
$suite->addTest(new TestSuite("objects_wbds_test"));
$suite->addTest(new TestSuite("chatlib_test"));
$suite->addTest(new TestSuite("chatlib_load_test"));
//$suite->addTest(new TestSuite("load_test"));

?>
