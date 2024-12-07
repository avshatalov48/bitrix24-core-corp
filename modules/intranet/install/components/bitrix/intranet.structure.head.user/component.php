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
		$arResult['SECTIONS'] = [];
		$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()->departmentRepository();
		$departmentCollection = $departmentRepository->getDepartmentByHeadId($arParams['ID']);
		foreach ($departmentCollection as $department)
		{
			$url = null;
			if ($arParams['DETAIL_URL'])
				$url = str_replace(
					array('#ID#', '#DEPARTMENT#', '#DEPARTMENT_ID#', '#DEPT#', '#DEPT_ID#'),
					$department->getId(),
					$arParams['DETAIL_URL']
				);

			$arResult['SECTIONS'][] = [
				'NAME' => $department->getName(),
				'URL' => $url
			];
		}

		$this->IncludeComponentTemplate();
	}
}
?>