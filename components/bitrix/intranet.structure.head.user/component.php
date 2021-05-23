<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return false;

if ($arParams['DATA'])
{
	$arResult['SECTIONS'] = $arParams['DATA'];
	$this->IncludeComponentTemplate();
}
else
{
	$arParams['ID'] = intval($arParams['ID']);
		
	if ($arParams['ID'] <= 0)
		return false;
		
	$arParams['CACHE_TIME'] = 3600;
		
	if ($this->StartResultCache(false, $arParams['ID']))
	{
		$dbRes = CIBlockSection::GetList(
			array('active_from' => 'desc'),
			array(
				'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure'),
				'UF_HEAD' => $arParams['ID'],
			),
			false
		);

		$arResult['SECTIONS'] = array();
		while ($arRes = $dbRes->Fetch())
		{
			if ($arParams['DETAIL_URL'])
				$arRes['URL'] = str_replace(
					array('#ID#', '#DEPARTMENT#', '#DEPARTMENT_ID#', '#DEPT#', '#DEPT_ID#'),
					$arRes['ID'],
					$arParams['DETAIL_URL']
				);
		
			$arResult['SECTIONS'][] = $arRes;
		}
		$this->IncludeComponentTemplate();
	}
}
?>