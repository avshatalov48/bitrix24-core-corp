<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CEventCalendar::BuildCalendarSceleton(array(
	'bExtranet' => $arResult['bExtranet'],
	'bReadOnly' => $arResult['bReadOnly'],
	'id' => $arResult['id'],
	'arCalendarsCount' => $arResult['arCalendarsCount'],
	'bSuperpose' => $arResult['bSuperpose'],
	'bSocNet' => $arResult['bSocNet'],
	'week_days' => $arResult['week_days'],
	'ownerType' => $arResult['ownerType'],
	'component' => $component,
	'JSConfig' => $arResult['JSConfig'],
	'JS_arEvents' => $arResult['JS_arEvents'],
	'JS_arSPEvents' => $arResult['JS_arSPEvents'],
	'bShowOutlookBanner' => $arResult['bShowOutlookBanner']
));
?>