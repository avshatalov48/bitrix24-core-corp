<?
/***************************************
	Form validator class
***************************************/

class CAllFormValidator
{
	function err_mess()
	{
		$module_id = "form";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." (".$arModuleVersion["VERSION"].")<br>Class: CAllFormValidator<br>File: ".__FILE__;
	}

	/**
	 * Get filtered list of validators assigned to current field
	 *
	 * @param int $FIELD_ID
	 * @param array $arFilter
	 * @return CDBResult
	 */
	function GetList($FIELD_ID, $arFilter = array(), &$by, &$order)
	{
		$arFilter["FIELD_ID"] = $FIELD_ID;
		return CFormValidator::__getList($arFilter, $by, $order);
	}
	
	/**
	 * Get filtered list of validators assigned to current form
	 *
	 * @param int $WEB_FORM_ID
	 * @param array $arFilter
	 * @return CDBResult
	 */
	function GetListForm($WEB_FORM_ID, $arFilter = array(), &$by, &$order)
	{
		$arFilter["WEB_FORM_ID"] = $WEB_FORM_ID;
		return CFormValidator::__getList($arFilter, $by, $order);
	}

	function __getList($arFilter = array(), &$by, &$order)
	{
		global $DB;
		
		$arBy = array("ACTIVE", "C_SORT", "VALIDATOR_SID", "FIELD_ID");
		$by = strtoupper($by);
		if (!in_array($by, $arBy)) 
			$by = "C_SORT";
		
		$order = strtoupper($order);
		if ($order != "ASC" && $order != "DESC")
			$order = "ASC";
		
		$arWhere = array();
		foreach ($arFilter as $key => $value)
		{
			switch ($key)
			{
				case "WEB_FORM_ID":
					$arWhere[] = "FORM_ID='".intval($value)."'";
				break;
				
				case "FIELD_ID":
					$arWhere[] = "FIELD_ID='".intval($value)."'";
				break;
			
				case "ACTIVE":
					$arWhere[] = "ACTIVE='".($value == "N" ? "N" : "Y")."'";
				break;
				
				case "NAME":
					$arWhere[] = "VALIDATOR_SID='".$DB->ForSql($value)."'";
				break;
			}
		}
		
		if (count($arWhere) > 0)
			$strWhere = "WHERE ".implode(" AND ", $arWhere);
		else
			$strWhere = "";
		
		$query = "SELECT * FROM b_form_field_validator ".$strWhere." ORDER BY ".$by." ".$order;
		$rsList = $DB->Query($query, false, __LINE__);
		
		$arCurrentValidators = array();
		$rsFullList = CFormValidator::GetAllList();
		$arFullList = $rsFullList->arResult;
		while ($arCurVal = $rsList->Fetch())
		{
			foreach ($arFullList as $key => $arVal)
			{
				if ($arVal["NAME"] == $arCurVal["VALIDATOR_SID"])
				{
					$arCurVal["NAME"] = $arVal["NAME"];
					unset($arCurVal["VALIDATOR_SID"]);
					if (strlen($arCurVal["PARAMS"]) > 0)
					{
						$arCurVal["PARAMS"] = CFormValidator::GetSettingsArray($arVal, $arCurVal["PARAMS"]);
						$arCurVal["PARAMS_FULL"] = CFormValidator::GetSettings($arVal);
					}
					$arCurrentValidators[] = $arCurVal;
					break;
				}
			}
		}
		
		unset($rsList);
		$rsList = new CDBResult();
		$rsList->InitFromArray($arCurrentValidators);

		return $rsList;
	}
	
	/**
	 * Get filtered list of all registered validators. Filter params: TYPE = array|string;
	 *
	 * @param array $arFilter
	 * @return array
	 */
	function GetAllList($arFilter = array())
	{
		if (is_array($arFilter) && count($arFilter) > 0)
		{
			$arType = $arFilter["TYPE"];
			
			$is_filtered = true;
		}
		else
		{
			$is_filtered = false;
		}
		
		$rsValList = GetModuleEvents("form", "onFormValidatorBuildList");
		if ($rsValList->SelectedRowsCount() > 0)
		{
			$arResult = array();
			while ($arValidator = $rsValList->Fetch())
			{
				$arValidatorInfo = ExecuteModuleEventEx($arValidator, $arParams = array());
				
				if ($is_filtered)
				{
					if (is_array($arValidatorInfo["TYPES"]))
					{
						if (
							(is_array($arType) && count(array_intersect($arType, $arValidatorInfo["TYPES"])))
							||
							(!is_array($arType) && in_array($arType, $arValidatorInfo["TYPES"]))
						)
						
						$arResult[] = $arValidatorInfo;
					}
				}
				else
				{
					$arResult[] = $arValidatorInfo;
				}
			}
		}
		else
		{
			return false;
		}
		
		unset($rsValList);
		$rsValList = new CDBResult;
		$rsValList->InitFromArray($arResult);
		
		return $rsValList;
	}
	
	/**
	 * Apply validator to value
	 *
	 * @param string $sValSID
	 * @param array $arParams
	 * @param mixed $arValue
	 * @return bool
	 */
	function Execute($arValidator, $arQuestion, $arAnswers, $arAnswerValues)
	{
		$rsValidators = CFormValidator::GetAllList();
		while ($arValidatorInfo = $rsValidators->Fetch())
		{
			if ($arValidatorInfo["NAME"] == $arValidator["NAME"])
				break;
		}
		
		if ($arValidatorInfo)
		{
			if ($arValidatorInfo["HANDLER"])
			{
				return call_user_func($arValidatorInfo["HANDLER"], $arValidator["PARAMS"], $arQuestion, $arAnswers, $arAnswerValues);
			}
		}
		
		return true;
	}
	
	/**
	 * Assign validator to the field
	 *
	 * @param int $WEB_FORM_ID
	 * @param int $FIELD_ID
	 * @param string $sValSID
	 * @param array $arParams
	 * @return int|bool
	 */
	function Set($WEB_FORM_ID, $FIELD_ID, $sValSID, $arParams = array(), $C_SORT = 100)
	{
		global $DB;
		
		$rsValList = CFormValidator::GetAllList();
		while ($arVal = $rsValList->Fetch())
		{
			if ($arVal["NAME"] == $sValSID)
			{
				$arQueryFields = array(
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"FORM_ID" => intval($WEB_FORM_ID),
					"FIELD_ID" => intval($FIELD_ID),
					"ACTIVE" => "Y",
					"C_SORT" => intval($C_SORT),
					"VALIDATOR_SID" => $DB->ForSql($sValSID),
				);
				
				if (count($arParams) > 0)
				{
					$strParams = CFormValidator::GetSettingsString($arVal, $arParams);
					$arQueryFields["PARAMS"] = $strParams;
				}
				
				return $DB->Add("b_form_field_validator", $arQueryFields);
			}
		}
	
		return false;
	}	
	
	/**
	 * Assign multiple validators to the field
	 *
	 * @param int $WEB_FORM_ID
	 * @param int $FIELD_ID
	 * @param array $arValidators
	 */
	function SetBatch($WEB_FORM_ID, $FIELD_ID, $arValidators)
	{
		global $DB;

		$rsValList = CFormValidator::GetAllList();
		$arValList = array();
		while ($arVal = $rsValList->Fetch())
		{
			$arValList[$arVal["NAME"]] = $arVal;
		}

		$C_SORT = 0;
		foreach ($arValidators as $key => $arFieldVal)
		{
			if ($arVal = $arValList[$arFieldVal["NAME"]])
			{
				$C_SORT += 100;
				$arQueryFields = array(
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"FORM_ID" => intval($WEB_FORM_ID),
					"FIELD_ID" => intval($FIELD_ID),
					"ACTIVE" => "Y",
					"C_SORT" => $C_SORT,
					"VALIDATOR_SID" => $arFieldVal["NAME"],
				);

				if (is_array($arFieldVal["PARAMS"]) && is_set($arVal, "CONVERT_TO_DB"))
				{
					$arParams = array();
					foreach ($arFieldVal["PARAMS"] as $key => $arParam)
					{
						$arParams[$arParam["NAME"]] = $arParam["VALUE"];
					}
				
					if (count($arParams) > 0)
					{
						$strParams = CFormValidator::GetSettingsString($arVal, $arParams);
						$arQueryFields["PARAMS"] = $strParams;
					}
				}
				
				$DB->Add("b_form_field_validator", $arQueryFields);
			}
		}
	}

	function GetSettingsString($arValidator, $arParams)
	{
		if (count($arParams) > 0 && is_set($arValidator, "CONVERT_TO_DB"))
		{
			$strParams = call_user_func($arValidator["CONVERT_TO_DB"], $arParams);
			return $strParams;
		}
	}
	
	function GetSettingsArray($arValidator, $strParams)
	{
		if (strlen($strParams) > 0 && is_set($arValidator, "CONVERT_FROM_DB"))
		{
			$arParams = call_user_func($arValidator["CONVERT_FROM_DB"], $strParams);
			return $arParams;
		}
	}
	
	function GetSettings($arValidator)
	{
		if (is_set($arValidator, "SETTINGS"))
		{
			$arSettings = call_user_func($arValidator["SETTINGS"]);
			return $arSettings;
		}
	}
	
	/**
	 * Clear all field validators
	 *
	 * @param int $FIELD_ID
	 */
	function Clear($FIELD_ID)
	{
		global $DB;
		$query = "DELETE FROM b_form_field_validator WHERE FIELD_ID='".$DB->ForSql($FIELD_ID)."'";
		$DB->Query($query, false, __LINE__);
	}
}
?>