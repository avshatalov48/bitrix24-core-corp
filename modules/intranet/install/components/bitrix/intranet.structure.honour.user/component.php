<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return false;

$arParams['ID'] = intval($arParams['ID']);
if ($arParams['ID'] <= 0)
	$arParams['ID'] = $USER->GetID();
	
if ($arParams['ID'] <= 0)
	return false;
	
$arParams['NUM_ENTRIES'] = intval($arParams['NUM_ENTRIES']);
if ($arParams['NUM_ENTRIES'] <= 0)
	$arParams['NUM_ENTRIES'] = 10;

$arParams['CACHE_TIME'] = 3600;
	
if ($this->StartResultCache(false, $arParams['ID'].'|'.$arParams['NUM_ENTRIES']))
{
	$dbRes = CIBlockElement::GetList(
		array('active_from' => 'desc'),
		array(
			'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_honour'),
			'PROPERTY_USER' => $arParams['ID'],
		),
		false,
		array('nTopCount' => $arParams['NUM_ENTRIES']),
		array(
			'ID', 'NAME', 'IBLOCK_ID', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO'
		)
	);

	$arResult['ENTRIES'] = array();
	while ($arRes = $dbRes->Fetch())
	{
		$arRes['TITLE'] = 
			$arRes['DETAIL_TEXT'] 
			? $arRes['DETAIL_TEXT'] 
			: (
				$arRes['PREVIEW_TEXT'] 
				? $arRes['PREVIEW_TEXT'] 
				: $arRes['NAME']
			);

		$arResult['ENTRIES'][] = $arRes;
	}

	$this->IncludeComponentTemplate();
}
?>