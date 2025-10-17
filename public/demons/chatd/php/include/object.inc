<? # $Id: object.inc,v 1.5 2006/09/27 07:28:52 agladysh Exp $

define('AT_INVALID',  0);
define('AT_BOOL',     1);
define('AT_INT',      2);
define('AT_STRING',   3);
define('AT_PTR',      4);
define('AT_LINKLIST', 5);
define('AT_FLOAT',    6);
define('AT_OBJECT',   7);

class CDObject {
  var $oId, $oType, $propList, $propFields, $propValues, $isDirty, $isEmpty, $linkList;

  function CDObject($id = false) {
    $this->id($id);
    $this->type(0);
    $this->propList = array();
    $this->propFields = array();
    $this->propValues = array();
    $this->linkList = array();
    $this->is_dirty(false);
    $this->is_empty(true);
//		$this->set(OBJPROP_OBJECT, ""); //  Looks like this is not needed.
  }

  function id($newId = -1) {
    if ($newId != -1) {
      $this->oId = $newId;
    }
    return $this->oId;
  }

  function type($newType = -1) {
    if ($newType != -1) {
      $this->oType = $newType;
    }
    return $this->oType;
  }

  function propType($propId = false) {
    return isset($this->propList[$propId]) ? $this->propList[$propId] : false;
  }

  function field($propId = false) {
    return isset($this->propFields[$propId]) ? $this->propFields[$propId] : false;
  }

  function propId($field = false) {
    return $field ? array_search($field, $this->propFields) : false;
  }

  function get($propList = false) {
    $retVal = false;
    if ($propList == false) {
      $retVal = $this->propValues;
    } elseif (!is_array($propList)) {
      $retVal = isset($this->propList[$propList]) && isset($this->propValues[$propList]) ? $this->propValues[$propList] : false;
    } else {
      foreach ($propList as $propId) {
        $retVal = array();
        if ((isset($this->propList[$propId])) && (isset($this->propValues[$propId]))) {
          $retVal[$propId] = $this->propValues[$propId];
        }
      }
    }
    return $retVal;
  }

  function set($propList = false, $propVal = false) {
    $retVal = false;
    if (is_array($propList)) {
      foreach ($propList as $propId => $propValue) {
        if (isset($this->propList[$propId])) {
          $this->propValues[$propId] = $propValue;
          $retVal = true;
        }
      }
    } else {
      $propId = $propList;
      if (isset($this->propList[$propId])) {
        $this->propValues[$propId] = $propVal;
        $retVal = true;
      }
    }
    $this->isDirty = $this->isDirty || $retVal;
    $this->isEmpty = $retVal ? false : $this->isEmpty; /* Brain-dead PHP (or me) */
    return $retVal;
  }

  function del($propList = false) {
    $retVal = false;
    if (is_array($propList)) {
      foreach ($propList as $propId => $propValue) {
        if ((isset($this->propList[$propId])) && isset($this->propValues[$propId])) {
          unset($this->propValues[$propId]);
          $retVal = true;
        }
      }
    } else {
      $propId = $propList;
      if ((isset($this->propList[$propId])) && isset($this->propValues[$propId])) {
        unset($this->propValues[$propId]);
        $retVal = true;
      }
    }
    $this->isDirty |= $retVal;
    $this->isEmpty = sizeof($this->propValues) == 0;
    return $retVal;
  }

  function is_dirty($dirtyFlag = -1) {
    if (intval($dirtyFlag) != -1) {
      $this->isDirty = !!$dirtyFlag;
    }
    return $this->isDirty;
  }

  function is_empty($emptyFlag = -1) {
    if (intval($emptyFlag) != -1) {
      $this->isEmpty = !!$emptyFlag;
    }
    return $this->isEmpty;
  }

  function getLink($objType = -1, $objId = -1, $returnIndex = false) {
    if ($this->isEmpty) {
      return false;
    }
    if (($objType == -1) && ($objId == -1)) {
      $retVal = &$this->linkList;
    } else {
      $retVal = array();
      foreach ($this->linkList as $key => $val) {
        if ($objType == -1) {
          if ($objId == $val[1]) {
            if (!$returnIndex) {
              $retVal[] = &$this->linkList[$key];
            } else {
              $retVal[] = $key;
            }
          }
        } else {
          if (($objType == $val[0] ) && (($objId == -1) || ( $objId == $val[1] ))) {
            if (!$returnIndex) {
              $retVal[] = &$this->linkList[$key];
            } else {
              $retVal[] = $key;
            }
          }
        }
      }
    }
    return $retVal;
  }

  function addLink($objType = -1, $objId = -1) {
    if (($objType == -1) || ($objId == -1)) {
      return false;
    }
    $linkId = sprintf("%08X%08X", $objType, $objId);
    $newVal = array(0 => $objType,1 => $objId);
    $this->linkList[$linkId] = $newVal;
    $this->is_dirty(true);
    $this->is_empty(false);
  }

  function delLink($objType = -1, $objId = -1) {
    $idxList = $this->getLink($objType, $objId, true);
    if (!$idxList) {
      return false;
    }
    foreach ($idxList as $idx) {
      unset($this->linkList[$idx]);
    }
    $this->is_dirty(true);
    return sizeof($idxList);
  }
}

include_once('include/objects.inc');

?>
