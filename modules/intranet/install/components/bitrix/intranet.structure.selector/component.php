<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('intranet'))
	return;

$arParams['FILTER_NAME'] =
		(empty($arParams["FILTER_NAME"]) || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $arParams["FILTER_NAME"])) ?
		'find_' : $arParams['FILTER_NAME'];

InitBVar($arParams['FILTER_DEPARTMENT_SINGLE']);
InitBVar($arParams['FILTER_SESSION']);

$arParams['LIST_PAGE'] = !empty($arParams['LIST_PAGE']) ? $arParams['LIST_PAGE'] : $APPLICATION->GetCurPage();

$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = $arParams['FILTER_NAME'].'_UF_DEPARTMENT';

if ($arParams['FILTER_DEPARTMENT_SINGLE'] == 'Y')
{
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;
}

$arResult['FILTER_PARAMS'] = array(
	$arParams['FILTER_NAME'].'_UF_DEPARTMENT',
	$arParams['FILTER_NAME'].'_UF_PHONE_INNER',
	$arParams['FILTER_NAME'].'_LAST_NAME',
	$arParams['FILTER_NAME'].'_LAST_NAME_RANGE',
	$arParams['FILTER_NAME'].'_POST',
	$arParams['FILTER_NAME'].'_COMPANY',
	$arParams['FILTER_NAME'].'_FIO',
	$arParams['FILTER_NAME'].'_EMAIL',
	$arParams['FILTER_NAME'].'_PHONE',
/*	$arParams['FILTER_NAME'].'_BIRTHDATE_FROM',
	$arParams['FILTER_NAME'].'_BIRTHDATE_TO',*/
	$arParams['FILTER_NAME'].'_KEYWORDS',
	$arParams['FILTER_NAME'].'_IS_ONLINE',
);

$filter_action = !empty($_REQUEST['set_filter_'.$arParams['FILTER_NAME']]) ? 'set' : 'get';

InitFilterEx($arResult['FILTER_PARAMS'], $arParams['FILTER_NAME'], $filter_action, $arParams['FILTER_SESSION'] == 'Y');
$arResult['bVarsFromForm'] = true;

$arResult['FILTER_VALUES'] = array();

if (!empty($_REQUEST['del_filter_'.$arParams['FILTER_NAME']]))
{
	$arResult['bVarsFromForm'] = false;
	DelFilterEx($arResult['FILTER_PARAMS'], $arParams['FILTER_NAME'], $arParams['FILTER_SESSION'] == 'Y');
}
else
{
	foreach ($arResult['FILTER_PARAMS'] as $var)
	{
		$arResult['FILTER_VALUES'][$var] = htmlspecialcharsex($GLOBALS[$var]);
	}

	//$GLOBALS['UF_DEPARTMENT'] = $GLOBALS[$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] = $arResult['FILTER_VALUES']['UF_DEPARTMENT'];
}

$this->IncludeComponentTemplate();

return $arResult['FILTER_VALUES'];
?>
