<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!IsModuleInstalled("bitrix24"))
	return;

use Bitrix\Main;
use Bitrix\Main\Localization\CultureTable;

$rsLang = CLanguage::GetByID("la");
if (!$rsLang->Fetch())
{
	$arFields = array(
		"NAME" => "la",
		"FORMAT_DATE" => "DD.MM.YYYY",
		"FORMAT_DATETIME" => "DD.MM.YYYY HH:MI:SS",
		"WEEK_START" => 1,
		"FORMAT_NAME" => "#NAME# #LAST_NAME#",
		"CHARSET" => "UTF-8",
		"DIRECTION" => "Y",
		"CODE" => "la",
	);

	$result = CultureTable::add($arFields);
	$cultureId = $result->getId();

	if ($cultureId)
	{
		$arLangFields = array(
			"LID"				=> "la",
			"ACTIVE"			=> "Y",
			"SORT"				=> 5,
			"DEF"				=> "N",
			"NAME"				=> "Spanish",
			"CULTURE_ID"		=> $cultureId,
		);

		$obLang = new CLanguage;
		$langID = $obLang->Add($arLangFields);

		if ($langID)
		{
			//spanish names for user fields
			$arLanguages = Array();
			$rsLanguage = CLanguage::GetList($by, $order, array());
			while($arLanguage = $rsLanguage->Fetch())
				$arLanguages[] = $arLanguage["LID"];

			$arUserFields = array("UF_CRM_TASK", "UF_CRM_CAL_EVENT" );

			foreach ($arUserFields as $userField)
			{
				$arLabelNames = Array();

				foreach($arLanguages as $languageID)
				{
					WizardServices::IncludeServiceLang("property_names.php", $languageID);

					$arLabelNames[$languageID] = GetMessage($userField);
				}

				$arProperty["EDIT_FORM_LABEL"] = $arLabelNames;
				$arProperty["LIST_COLUMN_LABEL"] = $arLabelNames;
				$arProperty["LIST_FILTER_LABEL"] = $arLabelNames;

				$dbRes = CUserTypeEntity::GetList(Array(), Array("FIELD_NAME" => $userField));
				if ($arRes = $dbRes->Fetch())
				{
					$userType = new CUserTypeEntity();
					$userType->Update($arRes["ID"], $arProperty);
				}
			}

			//currency translate
			if (CModule::IncludeModule("currency"))
			{
				$languageID = "la";
				WizardServices::IncludeServiceLang("currency.php", $languageID);

				$arCurrency = array(
					array(
						"FORMAT_STRING" => GetMessage("LA_CURRENCY_RUB_FORMAT"),
						"FULL_NAME" => GetMessage("LA_CURRENCY_RUB"),
						"DEC_POINT" => ".",
						"THOUSANDS_SEP" => false,
						"THOUSANDS_VARIANT" => "S",
						"DECIMALS" => 2,
						"CURRENCY" => "RUB",
						"LID" => "la"
					),
					array(
						"FORMAT_STRING" => "&euro;#",
						"FULL_NAME" => GetMessage("LA_CURRENCY_EUR"),
						"DEC_POINT" => ".",
						"THOUSANDS_SEP" => false,
						"THOUSANDS_VARIANT" => "C",
						"DECIMALS" => 2,
						"CURRENCY" => "EUR",
						"LID" => "la"
					),
					array(
						"FORMAT_STRING" => "$#",
						"FULL_NAME" => GetMessage("LA_CURRENCY_USD"),
						"DEC_POINT" => ".",
						"THOUSANDS_SEP" => false,
						"THOUSANDS_VARIANT" => "C",
						"DECIMALS" => 2,
						"CURRENCY" => "USD",
						"LID" => "la"
					),
					array(
						"FORMAT_STRING" => GetMessage("LA_CURRENCY_UAH_FORMAT"),
						"FULL_NAME" => GetMessage("LA_CURRENCY_UAH"),
						"DEC_POINT" => ".",
						"THOUSANDS_SEP" => false,
						"THOUSANDS_VARIANT" => "S",
						"DECIMALS" => 2,
						"CURRENCY" => "UAH",
						"LID" => "la"
					)
				);

				foreach($arCurrency as $currency)
				{
					$db_result_lang = CCurrencyLang::GetByID($currency["CURRENCY"], "la");
					if (!$db_result_lang)
						CCurrencyLang::Add($currency);
				}
			}

			//crm invoice status
			if (CModule::IncludeModule("sale"))
			{
				$statusesSort = array(
					'N' => 100,
					'A' => 120,
					'D' => 140,
					'P' => 130,
					'S' => 110,
					'F' => 200
				);
				$dbStatusList = CSaleStatus::GetList(
					array(),
					array('ID' => array_keys($statusesSort)),
					false,
					false,
					array('ID'));

				$arExistStatuses = array();

				while($arStatusList = $dbStatusList->Fetch())
					$arExistStatuses[$arStatusList['ID']] = $arStatusList;


				foreach ($statusesSort as $statusId => $statusSort)
				{
					$arLandData = array();

					foreach($arLanguages as $languageID)
					{
						if ($languageID == "la")
						{
							WizardServices::IncludeServiceLang("status.php", $languageID);

							$arLandData[] = array(
								"LID" => $languageID,
								"NAME" => GetMessage("CRM_STATUS_".$statusId),
								"DESCRIPTION" => GetMessage("CRM_STATUS_".$statusId."_DESCR")
							);
						}
						else
						{
							$arLangInfo = CSaleStatus::GetLangByID($statusId, $languageID);

							$arLandData[] = array(
								"LID" => $languageID,
								"NAME" => $arLangInfo["NAME"],
								"DESCRIPTION" => $arLangInfo["DESCRIPTION"]
							);
						}
					}

					if (array_key_exists($statusId, $arExistStatuses))
					{
						CSaleStatus::Update(
							$statusId,
							array(
								'SORT' => $statusSort,
								'LANG' => $arLandData
							)
						);
					}
				}
			}
		}
	}
}
?>