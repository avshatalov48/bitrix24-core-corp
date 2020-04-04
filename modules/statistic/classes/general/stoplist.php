<?php
IncludeModuleLangFile(__FILE__);

class CAllStopList
{
	public static function GetByID($STOPLIST_ID)
	{
		$STOPLIST_ID = intval($STOPLIST_ID);
		if($STOPLIST_ID <= 0)
			return false;

		$by = '';
		$order = '';
		$arFilter = array(
			"ID" => $STOPLIST_ID,
			"ID_EXACT_MATCH" => "Y",
		);
		$is_filtered = '';
		$rs = CStopList::GetList($by, $order, $arFilter, $is_filtered);

		return $rs;
	}

	function CheckFields($ID, &$arFields)
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		$aMsg = array();

		$arFields["TEST"] = "N";

		unset($arFields["ID"]);

		unset($arFields["TIMESTAMP_X"]);
		$arFields["~TIMESTAMP_X"] = $DB->GetNowFunction();

		if(strlen($arFields["SITE_ID"]) <= 0 || $arFields["SITE_ID"] == "NOT_REF")
			$arFields["SITE_ID"] = false;

		if($arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";

		if($arFields["SAVE_STATISTIC"] != "Y")
			$arFields["SAVE_STATISTIC"] = "N";

		$arIPFields = array("IP_1", "IP_2", "IP_3", "IP_4", "MASK_1", "MASK_2", "MASK_3", "MASK_4");
		foreach($arIPFields as $FIELD_ID)
		{
			if(strlen(trim($arFields[$FIELD_ID])) > 0)
			{
				$arFields[$FIELD_ID] = intval($arFields[$FIELD_ID]);
				if($arFields[$FIELD_ID] < 0)
					$arFields[$FIELD_ID] = 0;
				elseif($arFields[$FIELD_ID] > 255)
					$arFields[$FIELD_ID] = 255;
			}
			else
			{
				$arFields[$FIELD_ID] = false;
			}
		}

		if($arFields["USER_AGENT_IS_NULL"] != "Y")
			$arFields["USER_AGENT_IS_NULL"] = "N";

		if(strlen($arFields["DATE_END"]) > 0 && !CheckDateTime($arFields["DATE_END"]))
			$aMsg[] = array("id"=>"DATE_END", "text"=> GetMessage("STAT_WRONG_END_DATE"));

		if(strlen($arFields["DATE_START"]) > 0 && !CheckDateTime($arFields["DATE_START"]))
			$aMsg[] = array("id"=>"DATE_START", "text"=> GetMessage("STAT_WRONG_START_DATE"));

		$arTestFields = $arFields;
		$arTestFields["TEST"] = "Y";

		$TEST_ID = $DB->Add("b_stop_list", $arTestFields);
		$TEST_ID = intval($TEST_ID);

		$TEST_STOP_ID = $this->Check("Y");
		$TEST_STOP_ID = intval($TEST_STOP_ID);


		if($TEST_ID==$TEST_STOP_ID && $TEST_STOP_ID > 0 && $TEST_ID > 0)
			$aMsg[] = array("id"=>"WRONG_PARAMS", "text"=> GetMessage("STAT_WRONG_STOPLIST_PARAMS"));

		$DB->Query("DELETE FROM b_stop_list WHERE ID='".$TEST_ID."'");


		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}
		return true;
	}

	function Add($arFields)
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		if(!$this->CheckFields(false, $arFields))
			return false;

		$ID = $DB->Add("b_stop_list", $arFields);
		CStopList::CleanCache();

		return $ID;
	}

	function Update($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);

		if(!$this->CheckFields($ID, $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_stop_list", $arFields);
		if($strUpdate != "")
		{
			$res = $DB->Query("UPDATE b_stop_list SET ".$strUpdate." WHERE ID = ".$ID);
			CStopList::CleanCache();

			if(!$res)
				return false;
		}
		return true;
	}

	function SetActive($ID, $active = "N")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		if($ID)
		{
			if($active == "N")
			{
				$DB->Query("
					UPDATE b_stop_list
					SET ACTIVE='N',
					TIMESTAMP_X=".$DB->GetNowFunction()."
					WHERE ID = ".$ID
				);
			}
			else
			{
				$rs = $this->GetByID($ID);
				$ar = $rs->Fetch();
				if($ar && $ar["ACTIVE"] == "N")
				{
					$ar["ACTIVE"] = "Y";
					if(!$this->CheckFields($ID, $ar))
						return false;
					$DB->Query("
						UPDATE b_stop_list
						SET ACTIVE='Y',
						TIMESTAMP_X=".$DB->GetNowFunction()."
						WHERE ID = ".$ID
					);
				}
			}
			CStopList::CleanCache();
		}
		return true;
	}

	function Delete($ID)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		CStopList::CleanCache();
		return $DB->Query("DELETE FROM b_stop_list WHERE ID = ".$ID);
	}

	public static function CleanCache()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$file = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/managed_cache/".$DB->type."/b_stop_list";
		if(file_exists($file))
			unlink($file);
	}
}
