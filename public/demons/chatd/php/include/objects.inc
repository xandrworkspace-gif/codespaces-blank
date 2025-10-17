<? # $Id: objects.inc,v 1.6 2006/10/02 11:56:38 agladysh Exp $

define('OBJTYPE_MSG', 1);
define('OBJTYPE_AREA', 2);
define('OBJTYPE_USER', 3);
define('OBJTYPE_CLAN', 4);
define('OBJTYPE_TRADE', 5);
define('OBJTYPE_PARTY', 6);
define('OBJTYPE_RAID', 7);

define('OBJPROP_CONTENTS', 1);


class Area extends CDObject {
	function Area($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_AREA);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class User extends CDObject {
	function User($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_USER);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class Clan extends CDObject {
	function Clan($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_CLAN);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class Trade extends CDObject {
	function Trade($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_TRADE);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class Party extends CDObject {
	function Party($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_PARTY);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class Raid extends CDObject {
	function Raid($id = false) {
		parent::CDObject($id);
		parent::type(OBJTYPE_RAID);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
	}
}

class ChatMsg extends CDObject {
	function ChatMsg($id = false) {
		parent::CDObject($id);
		$this->propList = array(
			OBJPROP_CONTENTS => AT_STRING,
		);
		$this->propFields = array(
			OBJPROP_CONTENTS => "contents",
		);
		$this->type(OBJTYPE_MSG);
	}
}

?>