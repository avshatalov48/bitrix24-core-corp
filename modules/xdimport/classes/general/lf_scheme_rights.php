<?
IncludeModuleLangFile(__FILE__);

class CXDILFSchemeRights
{

	function CheckFields($action, &$arFields)
	{
		global $DB;
		$this->LAST_ERROR = "";
		$aMsg = array();

		if((($action == "update" && array_key_exists("SCHEME_ID", $arFields)) || $action == "add") && intval($arFields["SCHEME_ID"]) <= 0)
			$aMsg[] = array("id"=>"SCHEME_ID", "text"=>GetMessage("LFP_CLASS_SCHEME_RIGHT_ERR_SCHEME_ID"));
		if((($action == "update" && array_key_exists("GROUP_CODE", $arFields)) || $action == "add") && strlen($arFields["GROUP_CODE"]) == 0)
			$aMsg[] = array("id"=>"GROUP_CODE", "text"=>GetMessage("LFP_CLASS_SCHEME_RIGHT_ERR_ENTITY_GROUP_CODE"));

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
				"SCHEME_ID" => $arFields["SCHEME_ID"],
				"GROUP_CODE" => $arFields["GROUP_CODE"]
			)
		);
		$arTmp = $rsTmp->Fetch();
		if (!$arTmp)
		{
			$arFields["ID"] = 1;
			$DB->Add("b_xdi_lf_scheme_right", $arFields);
		}

		return true;
	}

	public static function DeleteBySchemeID($SchemeID)
	{
		global $DB;
		$SchemeID = intval($SchemeID);

		$strSql = "
			DELETE
			FROM b_xdi_lf_scheme_right
			WHERE SCHEME_ID = ".$SchemeID."
		";

		$res = $DB->Query($strSql);
		if(is_object($res))
			return true;
		else
		{
			$e = $APPLICATION->GetException();
			$strError = GetMessage("LFP_CLASS_SCHEME_RIGHT_DELETE_ERROR", array("#error_msg#" => is_object($e)? $e->GetString(): ''));
		}

		$APPLICATION->ResetException();
		$e = new CApplicationException($strError);
		$APPLICATION->ThrowException($e);
		return false;
	}

	public static function GetList($aSort=array(), $aFilter=array())
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
				case "SCHEME_ID":
					$arFilter[] = "SR.SCHEME_ID=".$val;
					break;
				case "GROUP_CODE":
					$arFilter[] = "SR.GROUP_CODE='".$val."'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"?"DESC":"ASC");
			switch(strtoupper($key))
			{
				case "SCHEME_ID":
					$arOrder[] = "SR.SCHEME_ID ".$ord;
					break;
				case "GROUP_CODE":
					$arOrder[] = "SR.GROUP_CODE ".$ord;
					break;
			}
		}
		if(count($arOrder) == 0)
			$arOrder[] = "SR.SCHEME_ID ASC";
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				SR.SCHEME_ID
				,SR.GROUP_CODE
			FROM
				b_xdi_lf_scheme_right SR
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function GetByID($ID)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "
			SELECT
				SR.*
			FROM b_xdi_lf_scheme_right SR
			WHERE SR.ID = ".$ID."
		";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function Set($SchemeID, $arRights = array(), $arEUV = array())
	{
		if (intval($SchemeID) <= 0)
			return false;

		if (!is_array($arRights))
			return false;

		if (
			!is_array($arEUV)
			|| !array_key_exists("ENTITY_TYPE", $arEUV)
			|| !array_key_exists("EVENT_ID", $arEUV)
		)
			return false;

		if (
			!array_key_exists("ENTITY_ID", $arEUV)
			|| intval($arEUV["ENTITY_ID"]) <= 0
		)
			$arEUV["ENTITY_ID"] = 0;

		CXDILFSchemeRights::DeleteBySchemeID($SchemeID);

		$obSchemeRights = new CXDILFSchemeRights();
		$obXDIUser = new CXDIUser();

		foreach($arRights as $prefix => $arRightsTmp)
		{
			if (in_array($prefix, array("UA", "UN")))
				$this->Add(
					array(
						"SCHEME_ID" => $SchemeID,
						"GROUP_CODE" => $prefix
					)
				);
			else
			{
				if (!is_array($arRightsTmp))
					continue;

				foreach ($arRightsTmp as $user_id_tmp)
				{
					if (intval($user_id_tmp) > 0)
					{
						$obXDIUser->Add(
							array(
								"USER_ID" => $user_id_tmp,
								"GROUP_CODE" => $prefix.$user_id_tmp
							)
						);
						
						$this->Add(
							array(
								"SCHEME_ID" => $SchemeID,
								"GROUP_CODE" => $prefix.$user_id_tmp
							)
						);
					}
				}			
			}
		}

		return true;
	}
}
?>