<?
$inDialog = (isset($_REQUEST["dialog"]) && (strtoupper($_REQUEST["dialog"]) == "Y"));

if ($inDialog)
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	$GLOBALS['APPLICATION']->RestartBuffer();
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
}
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
}

IncludeModuleLangFile(__FILE__);

if ($inDialog)
{
	$popupWindow = new CJSPopup('', '');
	$popupWindow->ShowTitlebar(GetMessage("DAV_HELP_NAME"));
	$popupWindow->StartContent();
}
else
{
	$APPLICATION->SetTitle(GetMessage("DAV_HELP_NAME"));
}

echo str_replace("#SERVER#", $_SERVER["SERVER_NAME"], GetMessage('DAV_HELP_TEXT'));

if ($inDialog)
{
	$popupWindow->StartButtons();
	$popupWindow->ShowStandardButtons(array('close'));
	$popupWindow->EndButtons();
}
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
}
?>