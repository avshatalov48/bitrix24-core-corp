<?

use Bitrix\Main\ModuleManager;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/timeman/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));

if (
	COption::GetOptionString("bitrix24", "absence_limits_enabled", "") !== "Y"
	|| ModuleManager::isModuleInstalled("timeman")
	|| (\Bitrix\Main\Loader::includeModule("bitrix24") && \Bitrix\Bitrix24\Feature::isFeatureEnabled("absence"))
)
{
	$workTimeStart = 9;
	$workTimeEnd = 18;
	if (Bitrix\Main\Loader::includeModule("calendar"))
	{
		$arCalendarSet = CCalendar::GetSettings(array('getDefaultForEmpty' => false));
		if ((int)$arCalendarSet['work_time_start'])
		{
			$workTimeStart = $arCalendarSet['work_time_start'];
		}
		if ((int)$arCalendarSet['work_time_end'])
		{
			$workTimeEnd = $arCalendarSet['work_time_end'];
		}
	}
	$APPLICATION->IncludeComponent("bitrix:intranet.absence.calendar", ".default", Array(
		 "FILTER_NAME"	=> "absence",
		 "FILTER_SECTION_CURONLY" => "N",
		 "DAY_START" => $workTimeStart,
		 "DAY_FINISH" => $workTimeEnd
	));
}
else
{
	?>
	<script>
		BX.ready(() => {
			BX.UI.InfoHelper.show("limit_absence_management");
		});
	</script>
	<?php
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>