<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
if(!$USER->IsAdmin())
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
	
if (!CModule::IncludeModule("xdimport"))
	return false;

IncludeModuleLangFile(__FILE__);

$sTableID = "tbl_xdi_lf_scheme_list";
$oSort = new CAdminSorting($sTableID, "ID", "ASC");
$lAdmin = new CAdminList($sTableID, $oSort);


if($lAdmin->EditAction())
{
	foreach($FIELDS as $ID=>$arFields)
	{
		if(!$lAdmin->IsUpdated($ID))
			continue;
		$DB->StartTransaction();
		$ID = IntVal($ID);
		$cData = new CXDILFScheme;
		if(($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch()))
		{
			foreach($arFields as $key=>$value)
				$arData[$key]=$value;
			if(!$cData->Update($ID, $arData))
			{
				$lAdmin->AddGroupError(GetMessage("LFP_SCHEME_LIST_SAVE_ERROR")." ".$cData->LAST_ERROR, $ID);
				$DB->Rollback();
			}
		}
		else
		{
			$lAdmin->AddGroupError(GetMessage("LFP_SCHEME_LIST_SAVE_ERROR")." ".GetMessage("LFP_SCHEME_LIST_NO_RECORD"), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

$arID = $lAdmin->GroupAction();
$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"])? "$_REQUEST[action]": "";
if(is_array($arID))
{
	foreach($arID as $ID)
	{
		if(strlen($ID) <= 0 || intval($ID) <= 0)
			continue;

		switch($action)
		{
			case "delete":
				if(!CXDILFScheme::Delete($ID))
				{
					$e = $APPLICATION->GetException();
					$lAdmin->AddUpdateError($e->GetString(), $ID);
				}
				break;
			case "deactivate":
			case "activate":
				$cData = new CXDILFScheme;
				$cData->Update($ID, array("ACTIVE" => ($action == "deactivate" ? "N": "Y")));
				break;
			default:
				break;
		}
	}
}

$arHeaders = array(
	array(
		"id" => "ID",
		"content" => GetMessage("LFP_SCHEME_LIST_ID"),
		"sort" => "ID",
		"align" => "right",
		"default" => true,
	),
	array(
		"id" => "NAME",
		"content" => GetMessage("LFP_SCHEME_LIST_NAME"),
		"sort" => "NAME",
		"align" => "left",
		"default" => true,
	),
	array(
		"id" => "ACTIVE",
		"content" => GetMessage("LFP_SCHEME_LIST_ACTIVE"),
		"sort" => "ACTIVE",
		"align" => "left",
		"default" => true,
	),
	array(
		"id" => "SORT",
		"content" => GetMessage("LFP_SCHEME_LIST_SORT"),
		"sort" => "SORT",
		"align" => "left",
		"default" => true,
	),
	array(
		"id" => "LID",
		"content" => GetMessage("LFP_SCHEME_LIST_LID"),
		"sort" => "LID",
		"align" => "left",
		"default" => true,
	),
	array(
		"id" => "TYPE",
		"content" => GetMessage("LFP_SCHEME_LIST_TYPE"),
		"sort" => "TYPE",
		"align" => "left",
		"default" => true,
	)
);
$lAdmin->AddHeaders($arHeaders);

$rsData = CXDILFScheme::GetList(array($by=>$order));
$rsData = new CAdminResult($rsData, $sTableID);
while(is_array($arRes = $rsData->GetNext()))
{
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);

	$row->AddInputField("NAME", array("size"=>20));
	$row->AddViewField("NAME", '<a href="xdi_lf_scheme_edit.php?ID='.$arRes["ID"].'&amp;lang='.LANG.'">'.$arRes["NAME"].'</a>');
	$row->AddEditField("LID", CLang::SelectBox("FIELDS[".$arRes["ID"]."][LID]", $arRes["LID"]));
	$row->AddInputField("SORT", array("size"=>20));
	$row->AddCheckField("ACTIVE");	
	$row->AddViewField("ID", '<a href="xdi_lf_scheme_edit.php?lang='.LANGUAGE_ID.'&ID='.$arRes["ID"].'">'.$arRes["ID"].'</a>');
	$row->AddViewField("TYPE", GetMessage("LFP_SCHEME_LIST_".$arRes["TYPE"]));

	$arActions = array(
		array(
			"ICON" => "edit",
			"DEFAULT" => true,
			"TEXT" => GetMessage("LFP_SCHEME_LIST_EDIT"),
			"ACTION" => $lAdmin->ActionRedirect('xdi_lf_scheme_edit.php?lang='.LANGUAGE_ID.'&ID='.$arRes["ID"])
		)
	);
	$arActions[] = array("SEPARATOR"=>"Y");

	if($arRes["ACTIVE"] === "Y")
	{
		$arActions[] = array(
			"TEXT"=>GetMessage("LFP_SCHEME_LIST_DEACTIVATE"),
			"ACTION"=>"if(confirm('".GetMessage("LFP_SCHEME_LIST_DEACTIVATE_CONF")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "deactivate")
		);
	}
	else
	{
		$arActions[] = array(
			"TEXT"=>GetMessage("LFP_SCHEME_LIST_ACTIVATE"),
			"ACTION"=>$lAdmin->ActionDoGroup($arRes["ID"], "activate")
		);
	}

	$arActions[] = array(
		"ICON"=>"delete",
		"TEXT"=>GetMessage("LFP_SCHEME_LIST_DELETE"),
		"ACTION"=>"if(confirm('".GetMessage("LFP_SCHEME_LIST_DELETE_CONF")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "delete")
	);
	
	if(!empty($arActions))
		$row->AddActions($arActions);

}

$arFooter = array(
	array(
		"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
		"value" => $rsData->SelectedRowsCount(),
	),
	array(
		"counter" => true,
		"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
		"value" => 0,
	),
);

$lAdmin->AddFooter($arFooter);

$lAdmin->AddGroupActionTable(Array(
	"delete"=>GetMessage("MAIN_ADMIN_LIST_DELETE"),
	"activate"=>GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
	"deactivate"=>GetMessage("MAIN_ADMIN_LIST_DEACTIVATE"),
));

$aAdd = array(
	array(
		"TEXT" => GetMessage("LFP_SCHEME_LIST_POST"),
		"TITLE" => GetMessage("LFP_SCHEME_LIST_POST"),
		"ACTION" => "window.location='".addslashes("/bitrix/admin/xdi_lf_scheme_edit.php")."?lang=".LANGUAGE_ID."&TYPE=POST';"
	),
	array(
		"TEXT" => GetMessage("LFP_SCHEME_LIST_RSS"),
		"TITLE" => GetMessage("LFP_SCHEME_LIST_RSS"),
		"ACTION" => "window.location='".addslashes("/bitrix/admin/xdi_lf_scheme_edit.php")."?lang=".LANGUAGE_ID."&TYPE=RSS';"
	)
);

if (IsModuleInstalled("webservice"))
	$aAdd[] = array(
		"TEXT" => GetMessage("LFP_SCHEME_LIST_XML"),
		"TITLE" => GetMessage("LFP_SCHEME_LIST_XML"),
		"ACTION" => "window.location='".addslashes("/bitrix/admin/xdi_lf_scheme_edit.php")."?lang=".LANGUAGE_ID."&TYPE=XML';"
	);

$aContext = array(
	array(
		"TEXT" => GetMessage("LFP_SCHEME_LIST_ADD"),
		"TITLE" => GetMessage("LFP_SCHEME_LIST_ADD_TITLE"),
		"ICON" => "btn_new",
		"MENU"=>$aAdd
	),
);

$lAdmin->AddAdminContextMenu($aContext, /*$bShowExcel=*/false);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("LFP_SCHEME_LIST_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>