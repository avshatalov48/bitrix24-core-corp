<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("crm"))
{
	ShowError(GetMessage("BPWC_NO_CRM_MODULE"));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;


if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_IBLOCK'));
	return;
}
if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",
	"edit" => "edit-#id#.php",
	"sync" => "sync-#id#.php",
);
$arDefaultUrlTemplatesN404 = array(
	"index" => "page=index",
	"edit" => "page=edit&id=#id#",
	"sync" => "page=sync&id=#id#",
);
$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();

$componentPage = "";

$arComponentVariables = array("page", "id", "sessid", "saveajax");

if ($_REQUEST["auth"]=="Y" && $USER->IsAuthorized())
	LocalRedirect($APPLICATION->GetCurPageParam("", array("login", "logout", "register", "forgot_password", "change_password", "backurl", "auth")));

?>
<script type="text/javascript">
	var extSaleGetRemoteFormLocal1 = {"TITLE":"<?= GetMessage("CRM_EXT_SALE_DEJ_TITLE1") ?>"};
</script>
<?
$APPLICATION->AddHeadScript($this->GetPath().'/wizard.js');

ob_start();
$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:intranet.user.selector.new',
	'',
	array(
		'NAME' => 'IMPORT_RESPONSIBLE',
		'VALUE' => 0,
		'MULTIPLE' => 'N',
		"POPUP" => "Y",
		"SITE_ID" => SITE_ID,
		"ON_SELECT" => "__BXOnImportResponsibleChange"
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group.selector", ".default", array(
		"BIND_ELEMENT" => "id_GROUP_TXT",
		"ON_SELECT" => "__BXOnImportGroupChange",
		"SELECTED" => 0
	), null, array("HIDE_ICONS" => "Y")
);
ob_end_clean();

if ($arParams["SEF_MODE"] == "Y")
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = CComponentEngine::ParseComponentPath($arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = "index";

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
		$arResult["PATH_TO_".strtoupper($url)] = $arParams["SEF_FOLDER"].$value;
}
else
{
	$arVariables = array();

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = "index";

	foreach ($arDefaultUrlTemplatesN404 as $url => $value)
		$arResult["PATH_TO_".strtoupper($url)] = $GLOBALS["APPLICATION"]->GetCurPageParam($value, $arComponentVariables);
}

if ($_REQUEST["auth"] == "Y")
	$componentPage = "auth";

$arResult = array_merge(
	array(
		"SEF_MODE" => $arParams["SEF_MODE"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"VARIABLES" => $arVariables,
		"ALIASES" => $arParams["SEF_MODE"] == "Y"? array(): $arVariableAliases,
	),
	$arResult
);

$this->IncludeComponentTemplate($componentPage);
?>