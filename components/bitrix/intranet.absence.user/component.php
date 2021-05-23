<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return false;

$arParams['ID'] = intval($arParams['ID']);
if ($arParams['ID'] <= 0)
	$arParams['ID'] = $USER->GetID();

if ($arParams['ID'] <= 0)
	return false;

$arParams['CALENDAR_IBLOCK_ID'] = intval($arParams['CALENDAR_IBLOCK_ID']);
if ($arParams['CALENDAR_IBLOCK_ID'] <= 0)
	$arParams['CALENDAR_IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_calendar');

$arParams['CACHE_TIME'] = 3600;

if ($this->StartResultCache(false, $arParams['ID']))
{
	$arResult['ENTRIES'] = CIntranetUtils::GetAbsenceData(
		array(
			'CALENDAR_IBLOCK_ID' => $arParams['CALENDAR_IBLOCK_ID'],
			'USERS' => array($arParams['ID']),
			'DATE_START' => date($DB->DateFormatToPHP(CLang::GetDateFormat("SHORT"))),
			'DATE_FINISH' => false,
			'PER_USER' => false,
		)
	);

	foreach ($arResult['ENTRIES'] as $key => $arRes)
	{
		$arResult['ENTRIES'][$key]['TITLE'] =
			$arRes['PROPERTY_STATE_VALUE']
			? $arRes['PROPERTY_STATE_VALUE']
			: (
				$arRes['DETAIL_TEXT']
				? $arRes['DETAIL_TEXT']
				: (
					$arRes['PREVIEW_TEXT']
					? $arRes['PREVIEW_TEXT']
					: $arRes['NAME']
				)
			);

		$arResult['ENTRIES'][$key]['DATE_ACTIVE_FROM'] = $arRes['DATE_FROM'];
		$arResult['ENTRIES'][$key]['DATE_ACTIVE_TO'] = $arRes['DATE_TO'];
	}

	$this->IncludeComponentTemplate();
}
?>