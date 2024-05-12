<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/work_report.php");

$APPLICATION->SetTitle(GetMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}
?>
<?
if (IsModuleInstalled("timeman"))
{
	$APPLICATION->IncludeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		array(
			"POPUP_COMPONENT_NAME" => "bitrix:timeman.report.weekly",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => array()
		)
	);
}
elseif (
	!\Bitrix\Intranet\Settings\Tools\ToolsManager::getInstance()->checkAvailabilityByToolId('worktime')
	&& (!\Bitrix\Main\Loader::includeModule('bitrix24') || \Bitrix\Bitrix24\Feature::isFeatureEnabled('timeman'))
)
{
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.settings.tool.stub',
		'',
		[
			'LIMIT_CODE' => 'limit_office_worktime_off',
			'MODULE' => 'timeman',
			'SOURCE' => 'report_weekly'
		],
	);
}
elseif (!(!IsModuleInstalled("timeman") && in_array($licenseType, array("company", "edu", "nfr"))))
{
	?>
	<script>
		BX.ready(() => {
			BX.UI.InfoHelper.show("limit_office_reports");
		});
	</script>
	<?php
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>