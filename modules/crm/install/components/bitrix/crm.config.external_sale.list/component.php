<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule("crm"))
	return false;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if (strlen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";
if (strlen($arParams["BP_VAR"]) <= 0)
	$arParams["ID_VAR"] = "id";

$arParams["PATH_TO_INDEX"] = trim($arParams["PATH_TO_INDEX"]);
if (strlen($arParams["PATH_TO_INDEX"]) <= 0)
	$arParams["PATH_TO_INDEX"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=index";

$arParams["PATH_TO_EDIT"] = trim($arParams["PATH_TO_EDIT"]);
if (strlen($arParams["PATH_TO_EDIT"]) <= 0)
	$arParams["PATH_TO_EDIT"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=edit&".$arParams["ID_VAR"]."=#id#";

$arParams["PATH_TO_SYNC"] = trim($arParams["PATH_TO_SYNC"]);
if (strlen($arParams["PATH_TO_SYNC"]) <= 0)
	$arParams["PATH_TO_SYNC"] = $APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=sync&".$arParams["ID_VAR"]."=#id#";

$arResult["FatalErrorMessage"] = "";
$arResult["ErrorMessage"] = "";

$arResult["PATH_TO_INDEX"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_INDEX"], array());
$arResult["PATH_TO_EDIT"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EDIT"], array("id" => 0));

if (strlen($arResult["FatalErrorMessage"]) <= 0)
{
	if ($_SERVER["REQUEST_METHOD"] == "GET" && strlen($_REQUEST["delete_id"]) > 0 && check_bitrix_sessid())
	{
		CCrmExternalSale::Delete($_REQUEST["delete_id"]);
		CAgent::RemoveAgent("CCrmExternalSaleImport::DataSync(".intval($_REQUEST["delete_id"]).");", "crm");
		LocalRedirect($APPLICATION->GetCurPageParam("", array("sessid", "delete_id", "check_id", "sync_id")));
	}
	elseif ($_SERVER["REQUEST_METHOD"] == "GET" && strlen($_REQUEST["check_id"]) > 0)
	{
		$errorMessage = "";

		$proxy = new CCrmExternalSaleProxy($_REQUEST["check_id"]);
		if (!$proxy->IsInitialized())
		{
			$errorMessage .= GetMessage("CRM_EXT_SALE_C1NO_CONNECT")."<br>";
		}
		else
		{
			$request = array(
				"METHOD" => "GET",
				"PATH" => "/bitrix/admin/sale_order_new.php",
				"HEADERS" => array(),
				"BODY" => array()
			);

			$response = $proxy->Send($request);
			if ($response == null)
			{
				$errorMessage .= GetMessage("CRM_EXT_SALE_C1ERROR_CONNECT")."<br>";
				$arErr = $proxy->GetErrors();
				foreach ($arErr as $err)
					$errorMessage .= sprintf("[%s] %s<br>", $err[0], htmlspecialcharsbx($err[1]));
			}
			elseif ($response["STATUS"]["CODE"] != 200)
			{
				$errorMessage .= sprintf(GetMessage("CRM_EXT_SALE_C1STATUS")."<br>", $response["STATUS"]["CODE"], $response["STATUS"]["PHRASE"]);
			}
			elseif (strpos($response["BODY"], "form_auth") !== false)
			{
				$errorMessage .= GetMessage("CRM_EXT_SALE_C1NO_AUTH")."<br>";
			}
		}

		$arResult["ErrorMessage"] .= $errorMessage;
		if (strlen($errorMessage) <= 0)
			$arResult["SuccessMessage"] = GetMessage("CRM_EXT_SALE_C1SUCCESS")."<br>";
	}
}

if (strlen($arResult["FatalErrorMessage"]) <= 0)
{
	$arResult["GRID_ID"] = "crm_config_external_sale";

	$gridOptions = new CGridOptions($arResult["GRID_ID"]);
	$gridColumns = $gridOptions->GetVisibleColumns();
	$gridSort = $gridOptions->GetSorting(array("sort"=>array("DATE_UPDATE" => "desc")));

	$arResult["HEADERS"] = array(
		array("id" => "NAME", "name" => GetMessage("BPWC_WLC_NAME"), "default" => true, "sort" => "NAME"),
		array("id" => "ACTIVE", "name" => GetMessage("BPWC_WLC_ACTIVE"), "default" => true, "sort" => "ACTIVE"),
		array("id" => "IMPORT_AGENT", "name" => GetMessage("BPWC_WLC_IMPORT_AGENT"), "default" => true, "sort" => ""),
		array("id" => "MESSAGE", "name" => GetMessage("BPWC_WLC_MESSAGE"), "default" => true, "sort" => ""),
		array("id" => "URL", "name" => GetMessage("BPWC_WLC_URL"), "default" => true, "sort" => ""),
		array("id" => "LAST_STATUS_DATE", "name" => GetMessage("BPWC_WLC_LAST_STATUS_DATE"), "default" => true, "sort" => "LAST_STATUS_DATE"),
		array("id" => "LAST_STATUS", "name" => GetMessage("BPWC_WLC_STATUS"), "default" => false, "sort" => "LAST_STATUS"),
		array("id" => "DATE_UPDATE", "name" => GetMessage("BPWC_WLC_DATE_UPDATE"), "default" => false, "sort" => "DATE_UPDATE"),
		array("id" => "IMPORT_PREFIX", "name" => GetMessage("BPWC_WLC_IMPORT_PREFIX"), "default" => false, "sort" => "IMPORT_PREFIX"),
		array("id" => "ID", "name" => "ID", "default" => false, "sort" => "ID"),
		array("id" => "DATE_CREATE", "name" => GetMessage("BPWC_WLC_DATE_CREATE"), "default" => false, "sort" => "DATE_CREATE"),
		//array("id" => "MODIFICATION_LABEL", "name" => GetMessage("BPWC_WLC_LABEL"), "default" => false, "sort" => "MODIFICATION_LABEL"),
		array("id" => "IMPORT_SIZE", "name" => GetMessage("BPWC_WLC_SIZE"), "default" => false, "sort" => "IMPORT_SIZE"),
		array("id" => "IMPORT_PERIOD", "name" => GetMessage("BPWC_WLC_IMPORT_PERIOD"), "default" => false, "sort" => "IMPORT_PERIOD"),
		array("id" => "IMPORT_PROBABILITY", "name" => GetMessage("BPWC_WLC_IMPORT_PROBABILITY"), "default" => false, "sort" => "IMPORT_PROBABILITY"),
		array("id" => "IMPORT_PUBLIC", "name" => GetMessage("BPWC_WLC_IMPORT_PUBLIC"), "default" => false, "sort" => "IMPORT_PUBLIC"),
	);

	$arResult["SORT"] = $gridSort["sort"];

	$arResult["RECORDS"] = array();

	$dbRecordsList = CCrmExternalSale::GetList(
		$gridSort["sort"],
		array()
	);
	while ($arRecord = $dbRecordsList->GetNext())
	{
		$path2Edit = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_EDIT"], array("id" => $arRecord["ID"]));
		$path2Sync = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_SYNC"], array("id" => $arRecord["ID"]));

		$agentInterval = 0;
		$dbAgents = CAgent::GetList(array(), array("NAME" => "CCrmExternalSaleImport::DataSync(".$arRecord["ID"].");", "MODULE_ID" => "crm", "ACTIVE" => "Y"));
		if ($arAgent = $dbAgents->Fetch())
			$agentInterval = intval($arAgent["AGENT_INTERVAL"] / 60);

		$v = htmlspecialcharsbx($arRecord["SCHEME"]."://".$arRecord["SERVER"].((intval($arRecord["PORT"]) > 0) ? ":".$arRecord["PORT"] : ""));
		$aCols = array(
			"URL" => "<a href=\"".$v."\" target=\"_blank\">".$v."</a>",
			"ACTIVE" => $arRecord["ACTIVE"] == "Y" ? GetMessage("BPWC_WLC_YES") : GetMessage("BPWC_WLC_NO"),
			"IMPORT_PUBLIC" => $arRecord["IMPORT_PUBLIC"] == "Y" ? GetMessage("BPWC_WLC_YES") : GetMessage("BPWC_WLC_NO"),
			"IMPORT_AGENT" => ($agentInterval > 0) ? $agentInterval : GetMessage("BPWC_WLC_MANUAL"),
			"MESSAGE" => "",
		);
		if (intval($arRecord["MODIFICATION_LABEL"]) == 0)
			$aCols["MESSAGE"] .= '<font class="errortext">'.GetMessage("BPWC_WLC_NEED_FIRST_SYNC1").'</font><br /><a href="'.$path2Sync.'" target="_self">'.GetMessage("BPWC_WLC_NEED_FIRST_SYNC1_DO").'</a><br />';
		if ($arRecord["LAST_STATUS"] != "" && strtolower(substr($arRecord["LAST_STATUS"], 0, strlen("success"))) != "success")
			$aCols["MESSAGE"] .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC3").$arRecord["LAST_STATUS"];
		if ($aCols["MESSAGE"] == "")
			$aCols["MESSAGE"] .= GetMessage("BPWC_WLC_NEED_FIRST_SYNC2");

		$aActions = array(
			array("ICONCLASS"=>"", "DEFAULT" => false, "TEXT"=>GetMessage("BPWC_WLC_SYNC"), "ONCLICK"=>"window.location='".$path2Sync."';"),
			array("SEPARATOR"=>true),
			array("ICONCLASS"=>"", "DEFAULT" => false, "TEXT"=>GetMessage("BPWC_WLC_CHECK"), "ONCLICK"=>"window.location='".$APPLICATION->GetCurPageParam("check_id=".$arRecord["ID"], array("sessid", "check_id", "delete_id", "sync_id"))."';"),
			array("SEPARATOR"=>true),
			array("ICONCLASS"=>"edit", "DEFAULT" => true, "TEXT"=>GetMessage("BPWC_WLC_NOT_DETAIL"), "ONCLICK"=>"window.location='".$path2Edit."';"),
			array("ICONCLASS"=>"delete", "TEXT"=>GetMessage("JHGFDC_STOP"), "ONCLICK"=>"if(confirm('".GetMessage("JHGFDC_STOP_ALT")."')) window.location='".$APPLICATION->GetCurPageParam("delete_id=".$arRecord["ID"]."&".bitrix_sessid_get(), array("sessid", "delete_id", "check_id", "sync_id"))."';")
		);

		$arResult["RECORDS"][] = array("data" => $arRecord, "actions" => $aActions, "columns" => $aCols, "editable" => false);
	}

	$arResult["ROWS_COUNT"] = $dbRecordsList->SelectedRowsCount();
	$arResult["NAV_STRING"] = $dbRecordsList->GetPageNavStringEx($navComponentObject, GetMessage("INTS_TASKS_NAV"), "", false);
	$arResult["NAV_CACHED_DATA"] = $navComponentObject->GetTemplateCachedData();
	$arResult["NAV_RESULT"] = $dbRecordsList;
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage("BPABL_PAGE_TITLE"));
$APPLICATION->AddChainItem(GetMessage("BPABL_PAGE_TITLE"));
?>