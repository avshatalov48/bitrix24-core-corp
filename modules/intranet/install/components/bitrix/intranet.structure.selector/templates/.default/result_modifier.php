<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$dbCurrentUser = CUser::GetByID($GLOBALS['USER']->GetID());
if (($arResult['CURRENT_USER'] = $dbCurrentUser->Fetch()) && $arResult['CURRENT_USER']['UF_DEPARTMENT'])
{
	$arResult['CURRENT_USER']['DEPARTMENT_TOP'] = CIntranetUtils::GetIBlockTopSection($arResult['CURRENT_USER']['UF_DEPARTMENT']);
}
?>