<?
IncludeModuleLangFile(__FILE__);

class CXDIUser
{

	function CheckFields($action, &$arFields)
	{
		global $DB;
		$this->LAST_ERROR = "";
		$aMsg = array();

		if((($action == "update" && array_key_exists("USER_ID", $arFields)) || $action == "add") && intval($arFields["USER_ID"]) <= 0)
			$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("LFP_CLASS_USER_ERR_USER_ID"));
		if((($action == "update" && array_key_exists("GROUP_CODE", $arFields)) || $action == "add") && strlen($arFields["GROUP_CODE"]) == 0)
			$aMsg[] = array("id"=>"GROUP_CODE", "text"=>GetMessage("LFP_CLASS_USER_ERR_ENTITY_GROUP_CODE"));

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}
		return true;
	}

	function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields("add", $arFields))
			return false;
		
		$rsTmp = $this->GetList(
			array(), 
			array(
				"USER_ID" => $arFields["USER_ID"],
				"GROUP_CODE" => $arFields["GROUP_CODE"]
			)
		);
		$arTmp = $rsTmp->Fetch();
		if (!$arTmp)
		{
			$arFields["ID"] = 1;
			$DB->Add("b_xdi_user_right", $arFields);
		}

		return true;
	}

	function Delete($ID)
	{
		global $DB, $APPLICATION;
		$strError = '';

		$res = $DB->Query("DELETE FROM b_xdi_user_right WHERE ID = ".intval($ID));
		if(is_object($res))
			return true;
		else
		{
			$e = $APPLICATION->GetException();
			$strError = GetMessage("LFP_CLASS_USER_DELETE_ERROR", array("#error_msg#" => is_object($e)? $e->GetString(): ''));
		}

		$APPLICATION->ResetException();
		$e = new CApplicationException($strError);
		$APPLICATION->ThrowException($e);
		return false;
	}

	function DeleteByUserID($UserID)
	{
		global $DB;

		$strSql = "
			DELETE
			FROM b_xdi_user_right
			WHERE USER_ID = ".intval($UserID)."
		";

		$res = $DB->Query($strSql);
		if(is_object($res))
			return true;
		else
		{
			$e = $APPLICATION->GetException();
			$strError = GetMessage("LFP_CLASS_USER_DELETE_ERROR", array("#error_msg#" => is_object($e)? $e->GetString(): ''));
		}

		$APPLICATION->ResetException();
		$e = new CApplicationException($strError);
		$APPLICATION->ThrowException($e);
		return false;
	}		

	function GetList($aSort=array(), $aFilter=array())
	{
		global $DB;

		$arFilter = array();
		foreach($aFilter as $key=>$val)
		{
			$val = $DB->ForSql($val);
			if(strlen($val)<=0)
				continue;
			switch(strtoupper($key))
			{
				case "USER_ID":
					$arFilter[] = "UR.USER_ID=".intval($val);
					break;
				case "GROUP_CODE":
					$arFilter[] = "UR.GROUP_CODE='".$val."'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"?"DESC":"ASC");
			switch(strtoupper($key))
			{
				case "USER_ID":
					$arOrder[] = "UR.USER_ID ".$ord;
					break;
				case "GROUP_CODE":
					$arOrder[] = "UR.GROUP_CODE ".$ord;
					break;
			}
		}
		if(count($arOrder) == 0)
			$arOrder[] = "UR.USER_ID ASC";
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				UR.USER_ID
				,UR.GROUP_CODE
			FROM
				b_xdi_user_right UR
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				UR.*
			FROM b_xdi_user_right UR
			WHERE SR.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

}
?>