<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."page-one-column");

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intranet/public_bitrix24/timeman/timeman.php");
$APPLICATION->SetTitle(Loc::getMessage("TITLE"));
$licenseType = "";
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	$licenseType = CBitrix24::getLicenseType();
}
?><?

if (ModuleManager::isModuleInstalled("timeman"))
{
	try
	{
		$APPLICATION->IncludeComponent("bitrix:timeman.schedules", "", []);
	}
	catch (\Bitrix\Main\AccessDeniedException $e)
	{
		echo $e->getMessage();
	}
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
			'SOURCE' => 'schedules'
		],
	);
}
elseif (!(!ModuleManager::isModuleInstalled("timeman") && in_array($licenseType, ["company", "edu", "nfr"])))
{
	?>
	<script>
		BX.ready(() => {
			BX.UI.InfoHelper.show("limit_office_shift_scheduling");
		});
	</script>
	<?php
}
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>