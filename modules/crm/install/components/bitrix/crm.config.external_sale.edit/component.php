<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("crm"))
	return false;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if ($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";
if ($arParams["ID_VAR"] == '')
	$arParams["ID_VAR"] = "id";

$arParams["PATH_TO_INDEX"] = trim($arParams["PATH_TO_INDEX"]);
if ($arParams["PATH_TO_INDEX"] == '')
	$arParams["PATH_TO_INDEX"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=index";

$arParams["PATH_TO_EDIT"] = trim($arParams["PATH_TO_EDIT"]);
if ($arParams["PATH_TO_EDIT"] == '')
	$arParams["PATH_TO_EDIT"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=edit&".$arParams["ID_VAR"]."=#id#";

$arParams["PATH_TO_SYNC"] = trim($arParams["PATH_TO_SYNC"]);
if ($arParams["PATH_TO_SYNC"] == '')
	$arParams["PATH_TO_SYNC"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=sync&".$arParams["ID_VAR"]."=#id#";

$arResult["FatalErrorMessage"] = "";
$arResult["ErrorMessage"] = "";

$arParams["ID"] = intval($arParams["ID"]);

$arResult["PATH_TO_INDEX"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_INDEX"], array());
$arResult["PATH_TO_SYNC"] = "";
if ($arParams["ID"] > 0)
	$arResult["PATH_TO_SYNC"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SYNC"], array("id" => $arParams["ID"]));

$arResult["DAS_IST_SHOP_LIMIT"] = false;

if ($arResult["FatalErrorMessage"] == '')
{
	if ($arParams["ID"] > 0)
	{
		$dbRecordsList = CCrmExternalSale::GetList(
			array(),
			array("ID" => $arParams["ID"])
		);
		if ($arRecord = $dbRecordsList->GetNext())
			$arResult["BP"] = $arRecord;
		else
			$arResult["FatalErrorMessage"] .= GetMessage("BPWC_WLC_WRONG_BP").". ";

		$arResult["BP"]["DATA_SYNC_PERIOD"] = 0;
		$dbAgents = CAgent::GetList(array(), array("NAME" => "CCrmExternalSaleImport::DataSync(".$arParams["ID"].");", "MODULE_ID" => "crm", "ACTIVE" => "Y"));
		if ($arAgent = $dbAgents->Fetch())
			$arResult["BP"]["DATA_SYNC_PERIOD"] = intval($arAgent["AGENT_INTERVAL"] / 60);
	}
	else
	{
		$cnt = CCrmExternalSale::Count();
		$arResult["BP"] = array("ACTIVE" => "Y", "SCHEME" => "http", "PORT" => 80, "IMPORT_SIZE" => 10, "IMPORT_PERIOD" => 7, "DATA_SYNC_PERIOD" => 10, "IMPORT_PREFIX" => "EShop".($cnt + 1));

		$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
		if ($arLimitationSettings["MAX_SHOPS"] > 0)
		{
			if ($cnt >= $arLimitationSettings["MAX_SHOPS"])
			{
				$arResult["DAS_IST_SHOP_LIMIT"] = true;
				$arResult["ErrorMessage"] .= GetMessage("BPWC_WNC_MAX_SHOPS")."<br>";
			}
		}
	}
}

if ($arResult["FatalErrorMessage"] == '')
{
	if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['save']) || isset($_POST['apply'])) && check_bitrix_sessid())
	{
		$errorMessage = "";

		$arResult["BP"] = array(
			"~NAME" => $_POST["NAME"],
			"NAME" => htmlspecialcharsbx($_POST["NAME"]),
			"~ACTIVE" => ($_POST["ACTIVE"] == "Y") ? "Y" : "N",
			"ACTIVE" => ($_POST["ACTIVE"] == "Y") ? "Y" : "N",
			"~LOGIN" => $_POST["LOGIN"],
			"LOGIN" => htmlspecialcharsbx($_POST["LOGIN"]),
			"~IMPORT_SIZE" => 10,
			"IMPORT_SIZE" => 10,
			"~IMPORT_PERIOD" => intval($_POST["IMPORT_PERIOD"]),
			"IMPORT_PERIOD" => intval($_POST["IMPORT_PERIOD"]),
			"~IMPORT_PROBABILITY" => intval($_POST["IMPORT_PROBABILITY"]),
			"IMPORT_PROBABILITY" => intval($_POST["IMPORT_PROBABILITY"]),
			"~IMPORT_RESPONSIBLE" => (intval($_POST["IMPORT_RESPONSIBLE"]) > 0) ? intval($_POST["IMPORT_RESPONSIBLE"]) : false,
			"IMPORT_RESPONSIBLE" => (intval($_POST["IMPORT_RESPONSIBLE"]) > 0) ? intval($_POST["IMPORT_RESPONSIBLE"]) : false,
			"~IMPORT_PUBLIC" => ($_POST["IMPORT_PUBLIC"] == "Y") ? "Y" : "N",
			"IMPORT_PUBLIC" => ($_POST["IMPORT_PUBLIC"] == "Y") ? "Y" : "N",
			"~IMPORT_PREFIX" => $_POST["IMPORT_PREFIX"],
			"IMPORT_PREFIX" => htmlspecialcharsbx($_POST["IMPORT_PREFIX"]),
			"~SCHEME" => ($_POST["SCHEME"] == "https") ? "https" : "http",
			"SCHEME" => ($_POST["SCHEME"] == "https") ? "https" : "http",
			"~SERVER" => $_POST["SERVER"],
			"SERVER" => htmlspecialcharsbx($_POST["SERVER"]),
			"~PORT" => (intval($_POST["PORT"]) > 0) ? intval($_POST["PORT"]) : 80,
			"PORT" => (intval($_POST["PORT"]) > 0) ? intval($_POST["PORT"]) : 80,
			"~IMPORT_GROUP_ID" => (intval($_POST["IMPORT_GROUP_ID"]) > 0) ? intval($_POST["IMPORT_GROUP_ID"]) : false,
			"IMPORT_GROUP_ID" => (intval($_POST["IMPORT_GROUP_ID"]) > 0) ? intval($_POST["IMPORT_GROUP_ID"]) : false,
			//"~MODIFICATION_LABEL" => intval($_POST["MODIFICATION_LABEL"]),
			//"MODIFICATION_LABEL" => intval($_POST["MODIFICATION_LABEL"]),
			"DATA_SYNC_PERIOD" => intval($_POST["DATA_SYNC_PERIOD"]),
		);
		if ($_POST["PASSWORD"] <> '')
		{
			$arResult["BP"]["~PASSWORD"] = $_POST["PASSWORD"];
			$arResult["BP"]["PASSWORD"] = htmlspecialcharsbx($_POST["PASSWORD"]);
		}

		if ($_POST["LOGIN"] == '')
			$errorMessage .= GetMessage("BPWC_WNC_EMPTY_LOGIN")."<br>";
		if ($_POST["SERVER"] == '')
			$errorMessage .= GetMessage("BPWC_WNC_EMPTY_URL")."<br>";
		if ($_POST["PASSWORD"] == '' && $arParams["ID"] <= 0)
			$errorMessage .= GetMessage("BPWC_WNC_EMPTY_PASSWORD")."<br>";

		$arLimitationSettings = CCrmExternalSale::GetLimitationSettings();
		if ($arLimitationSettings["MAX_DAYS"] > 0 && $arResult["BP"]["IMPORT_PERIOD"] > $arLimitationSettings["MAX_DAYS"])
			$arResult["BP"]["IMPORT_PERIOD"] = $arResult["BP"]["~IMPORT_PERIOD"] = $arLimitationSettings["MAX_DAYS"];
		if ($arLimitationSettings["MAX_SHOPS"] > 0 && $arParams["ID"] <= 0)
		{
			$cnt = CCrmExternalSale::Count();
			if ($cnt >= $arLimitationSettings["MAX_SHOPS"])
				$errorMessage .= GetMessage("BPWC_WNC_MAX_SHOPS")."<br>";
		}

		if ($errorMessage == '')
		{
			$arFields = array(
				"NAME" => $arResult["BP"]["~NAME"],
				"ACTIVE" => $arResult["BP"]["~ACTIVE"],
				"LOGIN" => $arResult["BP"]["~LOGIN"],
				"IMPORT_SIZE" => $arResult["BP"]["~IMPORT_SIZE"],
				"IMPORT_PERIOD" => $arResult["BP"]["~IMPORT_PERIOD"],
				"IMPORT_PROBABILITY" => $arResult["BP"]["~IMPORT_PROBABILITY"],
				"IMPORT_RESPONSIBLE" => $arResult["BP"]["~IMPORT_RESPONSIBLE"],
				"IMPORT_PUBLIC" => $arResult["BP"]["~IMPORT_PUBLIC"],
				"IMPORT_PREFIX" => $arResult["BP"]["~IMPORT_PREFIX"],
				"IMPORT_ERRORS" => 0,
				"SCHEME" => $arResult["BP"]["~SCHEME"],
				"SERVER" => $arResult["BP"]["~SERVER"],
				"PORT" => $arResult["BP"]["~PORT"],
				"IMPORT_GROUP_ID" => $arResult["BP"]["~IMPORT_GROUP_ID"],
				"COOKIE" => false,
			);

			if ($_POST["SERVER"] <> '')
			{
				$arCrmUrl = parse_url($_POST["SERVER"]);
				$crmUrlHost = $arCrmUrl["host"] ? $arCrmUrl["host"] : $arCrmUrl["path"];
				$crmUrlScheme = $arCrmUrl["scheme"]? mb_strtolower($arCrmUrl["scheme"]) : mb_strtolower($_POST["SCHEME"]);
				if (!in_array($crmUrlScheme, array('http', 'https')))
					$crmUrlScheme = 'http';
				$crmUrlPort = $arCrmUrl["port"] ? intval($arCrmUrl["port"]) : intval($_POST["PORT"]);
				if ($crmUrlPort <= 0)
					$crmUrlPort = $crmUrlScheme == 'https' ? 443 : 80;
				$arFields["SCHEME"] = $crmUrlScheme;
				$arFields["SERVER"] = $crmUrlHost;
				$arFields["PORT"] = $crmUrlPort;
			}

			if ($arParams["ID"] > 0)
			{
				//$arFields["MODIFICATION_LABEL"] = $_POST["MODIFICATION_LABEL"];
				if ($_POST["PASSWORD"] <> '')
					$arFields["PASSWORD"] = $_POST["PASSWORD"];
				$res = CCrmExternalSale::Update($arParams["ID"], $arFields);
			}
			else
			{
				$arFields["PASSWORD"] = $_POST["PASSWORD"];
				$res = CCrmExternalSale::Add($arFields);
			}

			if (!$res)
			{
				if ($ex = $GLOBALS["APPLICATION"]->GetException())
					$errorMessage .= $ex->GetString().".<br>";
				else
					$errorMessage .= "Unknown error."."<br>";
			}
		}

		if ($errorMessage == '')
		{
			$dbAgents = CAgent::GetList(array(), array("NAME" => "CCrmExternalSaleImport::DataSync(".intval($res).");", "MODULE_ID" => "crm"));
			if ($arAgent = $dbAgents->Fetch())
			{
				if ($arResult["BP"]["DATA_SYNC_PERIOD"] > 0)
				{
					if ($arAgent["ACTIVE"] != "Y" || intval($arAgent["AGENT_INTERVAL"] / 60) != $arResult["BP"]["DATA_SYNC_PERIOD"])
						CAgent::Update($arAgent["ID"], array(
							"ACTIVE" => "Y",
							"AGENT_INTERVAL" => 60 * $arResult["BP"]["DATA_SYNC_PERIOD"],
							"RUNNING" => "N",
							"RETRY_COUNT" => 0
						));
				}
				else
				{
					CAgent::RemoveAgent("CCrmExternalSaleImport::DataSync(".intval($res).");", "crm");
				}
			}
			else
			{
				if ($arResult["BP"]["DATA_SYNC_PERIOD"] > 0)
					CAgent::AddAgent("CCrmExternalSaleImport::DataSync(".intval($res).");", "crm", "N", 60 * $arResult["BP"]["DATA_SYNC_PERIOD"]);
			}

			if (isset($_POST['apply']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EDIT"], array("id" => intval($res))));

			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SYNC"], array("id" => intval($res))));
			//LocalRedirect($arResult["PATH_TO_INDEX"]);
		}
		else
		{
			$arResult["ErrorMessage"] .= $errorMessage;
		}
	}
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage("BPABL_PAGE_TITLE"));
$APPLICATION->AddChainItem(GetMessage("BPABL_PAGE_TITLE"), $arResult["PATH_TO_INDEX"]);
?>