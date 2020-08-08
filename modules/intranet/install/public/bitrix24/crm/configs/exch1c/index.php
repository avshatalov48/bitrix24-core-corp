<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/configs/exch1c/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));

$templateName = ".default";
if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
{
	$f = preg_match("/(project|tf|crm)$/is", $license_name, $matches);
	if ($matches[0] <> '')
		$templateName = "free";
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.config.exch1c',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			"SEF_MODE" => "Y",
			"SEF_FOLDER" => "/crm/configs/exch1c/",
			"PATH_TO_CONFIGS_INDEX" => "/crm/configs/"
		]
	]
);
?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>