<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

$arResult["UPDATE_LIST"] = array();
$arResult["UPDATES_NUM"] = 0;
$errorMessage = "";

if (CUpdateClient::Lock())
{
	if ($arUpdateList = CUpdateClient::GetUpdatesList($errorMessage, LANGUAGE_ID))
	{
		$arResult["UPDATE_LIST"] = $arUpdateList;

		if ($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"])
		{
			$timestamp = MakeTimeStamp($arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_SOURCE"], "YYYY-MM-DD");
			$arResult["UPDATE_LIST"]["CLIENT"][0]["@"]["DATE_TO_FORMAT"] = ConvertTimeStamp($timestamp);
		}

		if (
			isset($arResult["UPDATE_LIST"]["MODULES"])
			&& is_array($arResult["UPDATE_LIST"]["MODULES"])
			&& isset($arResult["UPDATE_LIST"]["MODULES"][0]["#"]["MODULE"])
			&& is_array($arResult["UPDATE_LIST"]["MODULES"][0]["#"]["MODULE"])
		)
		{
			$arResult["UPDATES_NUM"] = count($arResult["UPDATE_LIST"]["MODULES"][0]["#"]["MODULE"]);
		}

		$arResult["CLIENT_MODULES"] = CUpdateClient::GetCurrentModules($errorMessage);

		$events = GetModuleEvents("main", "OnUpdateCheck");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEvent($arEvent, $errorMessage);

		$countModuleUpdates = 0;
		$countLangUpdatesInst = 0;
		$countLangUpdatesOther = 0;
		$countTotalImportantUpdates = 0;
		$countHelpUpdatesInst = 0;
		$countHelpUpdatesOther = 0;
		$bLockControls = !empty($errorMessage);

		if (isset($arUpdateList["MODULES"]) && is_array($arUpdateList["MODULES"]) && isset($arUpdateList["MODULES"][0]["#"]["MODULE"]) && is_array($arUpdateList["MODULES"][0]["#"]["MODULE"]))
			$countModuleUpdates = count($arUpdateList["MODULES"][0]["#"]["MODULE"]);

		if (isset($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"]) && isset($arUpdateList["LANGS"][0]["#"]["INST"]) && is_array($arUpdateList["LANGS"][0]["#"]["INST"]) && is_array($arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"]))
			$countLangUpdatesInst = count($arUpdateList["LANGS"][0]["#"]["INST"][0]["#"]["LANG"]);

		if (isset($arUpdateList["LANGS"]) && is_array($arUpdateList["LANGS"]) && isset($arUpdateList["LANGS"][0]["#"]["OTHER"]) && is_array($arUpdateList["LANGS"][0]["#"]["OTHER"]) && is_array($arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"]))
			$countLangUpdatesOther = count($arUpdateList["LANGS"][0]["#"]["OTHER"][0]["#"]["LANG"]);

		$arClientModules = CUpdateClient::GetCurrentModules($strError_tmp);
		$countTotalImportantUpdates = $countLangUpdatesInst;
		if ($countModuleUpdates > 0)
		{
			for ($i = 0, $cnt = count($arUpdateList["MODULES"][0]["#"]["MODULE"]); $i < $cnt; $i++)
			{
				$countTotalImportantUpdates += count($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["#"]["VERSION"]);
				if (!array_key_exists($arUpdateList["MODULES"][0]["#"]["MODULE"][$i]["@"]["ID"], $arClientModules))
					$countTotalImportantUpdates += 1;
			}
		}

		$countHelpUpdatesInst = 0;
		if (isset($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"]) && isset($arUpdateList["HELPS"][0]["#"]["INST"]) && is_array($arUpdateList["HELPS"][0]["#"]["INST"]) && is_array($arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"]))
			$countHelpUpdatesInst = count($arUpdateList["HELPS"][0]["#"]["INST"][0]["#"]["HELP"]);

		$countHelpUpdatesOther = 0;
		if (isset($arUpdateList["HELPS"]) && is_array($arUpdateList["HELPS"]) && isset($arUpdateList["HELPS"][0]["#"]["OTHER"]) && is_array($arUpdateList["HELPS"][0]["#"]["OTHER"]) && is_array($arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"]))
			$countHelpUpdatesOther = count($arUpdateList["HELPS"][0]["#"]["OTHER"][0]["#"]["HELP"]);

		$arResult["COUNT_MODULE_UPDATES"] = $countModuleUpdates;
		$arResult["COUNT_LANG_UPDATES"] = $countLangUpdatesInst;
		$arResult["COUNT_TOTAL_IMPORTANT_UPDATES"] = $countTotalImportantUpdates;


		//license
		$newLicenceSignedKey = CUpdateClient::getNewLicenseSignedKey();
		$newLicenceSigned = COption::GetOptionString("main", $newLicenceSignedKey, "N");

		$arResult["IS_LICENSE_SIGNED"] = $newLicenceSigned == "Y";

		$bLicenseNotFound = False;
		if ($arUpdateList !== false
			&& isset($arUpdateList["ERROR"])
			&& count($arUpdateList["ERROR"]) > 0)
		{
			for ($i = 0, $cntTmp = count($arUpdateList["ERROR"]); $i < $cntTmp; $i++)
			{
				if ($arUpdateList["ERROR"][$i]["@"]["TYPE"] == "LICENSE_NOT_FOUND")
				{
					$bLicenseNotFound = True;
					break;
				}
			}
		}
		$strLicenseKeyTmp = CUpdateClient::GetLicenseKey();
		$bLicenseNotFound = strlen($strLicenseKeyTmp) <= 0 || strtolower($strLicenseKeyTmp) == "demo" || $bLicenseNotFound;

		$arResult["IS_LICENSE_FOUND"] = !$bLicenseNotFound;

	}
	else
	{
		$errorMessage = GetMessage("SUP_CANT_CONNECT");
	}
}

if (!empty($errorMessage))
{
	ShowError($errorMessage);
	return;
}

$this->IncludeComponentTemplate();
?>
