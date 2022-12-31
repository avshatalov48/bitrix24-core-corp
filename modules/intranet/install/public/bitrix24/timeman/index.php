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
	if (LANGUAGE_ID == "de" || LANGUAGE_ID == "la")
	{
		$lang = LANGUAGE_ID;
	}
	else
	{
		$lang = LangSubst(LANGUAGE_ID);
	}
	?>
	<p><?=GetMessage("TARIFF_RESTRICTION_TEXT")?></p>
	<div style="text-align: center;"><img src="images/<?=$lang?>/absence.png"/></div>
	<p><?=GetMessage("ABSENCE_TARIFF_RESTRICTION_TITLE")?></p>
	<br/>
	<?php if (\Bitrix\Main\Loader::includeModule('bitrix24')): ?>
		<div style="text-align: center;"><?CBitrix24::showTariffRestrictionButtons("absence")?></div>
	<?php endif;?>
	<?
}
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>