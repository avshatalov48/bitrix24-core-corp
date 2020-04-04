<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/iblock_e_rights.php")));
__IncludeLang($file);
$iblock_id = intval($_REQUEST["IBLOCK_ID"]);

$popupWindow = new CJSPopup(GetMessage("WD_POPUP_PERMISSIONS"));
//$popupWindow = new CJSPopup(GetMessage("WD_POPUP_PERMISSIONS"));
if (!(CModule::IncludeModule("iblock")))
	return false; 
elseif (!(CModule::IncludeModule("webdav")))
	return false; 
//elseif ($iblock_id <= 0)
	//$popupWindow->ShowError(GetMessage("WD_IBLOCK_ID_EMPTY"));

//HTML output
$popupWindow->ShowTitlebar();
if (isset($strWarning) && $strWarning != "")
	$popupWindow->ShowValidationError($strWarning);
	
$popupWindow->StartContent();
	$APPLICATION->IncludeComponent("bitrix:webdav.iblock.rights", ".default", Array(
			"IBLOCK_ID"		=> $_REQUEST['IBLOCK_ID'],
			"ENTITY_TYPE"	=> $_REQUEST['ENTITY_TYPE'],
			"ENTITY_ID"		=> $_REQUEST['ENTITY_ID'],
			"SOCNET_TYPE"	=> $_REQUEST['SOCNET_TYPE'],
			"SOCNET_ID"		=> $_REQUEST['SOCNET_ID'],
			"TAB_ID" 		=> 'tab_permissions',
			"SET_TITLE"		=>	"N",
			"SET_NAV_CHAIN"	=>	"N",
			"DO_NOT_REDIRECT" => true,
			"POPUP_DIALOG" => true,
		),
		null,
		array("HIDE_ICONS" => "Y")
	);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$popupWindow->Close($bReload = true, $_REQUEST["back_url"]);
	die();
}
?>

<div class="buttons">
<input type="hidden" name="save" value="Y" />
</div>
<?
$popupWindow->EndContent();
$popupWindow->ShowStandardButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>
