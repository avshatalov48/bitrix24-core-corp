<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/timeman.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}

if (IsModuleInstalled("timeman") && \Bitrix\Main\Loader::includeModule('timeman'))
{
	$APPLICATION->includeComponent(
		"bitrix:ui.sidepanel.wrapper",
		"",
		[
			"POPUP_COMPONENT_NAME" => "bitrix:timeman.worktime.stats",
			"POPUP_COMPONENT_TEMPLATE_NAME" => "",
			"POPUP_COMPONENT_PARAMS" => [
				'SCHEDULE_ID' => $_REQUEST['SCHEDULE_ID'] ?? null,
			],
			"USE_UI_TOOLBAR" => "Y"
		]
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
			'SOURCE' => 'worktime_grid'
		],
	);
}
elseif (!(!IsModuleInstalled("timeman") && in_array($licenseType, array("company", "edu", "nfr"))))
{
	?>
	<script>
		BX.ready(() => {
			BX.UI.InfoHelper.show("limit_office_worktime");
		});
	</script>
	<?php
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>