<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['USER_PROP'] = array();

TrimArr($arParams['USER_PROPERTY']);

$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arResult['USER_PROP'][$val["FIELD_NAME"]] = (strlen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

foreach ($arResult['USERS'] as $arUser)
{
	foreach ($arUser['UF_DEPARTMENT'] as $dept_id => $dept)
	{
		if (
			!is_array($arResult['FILTER_VALUES']['UF_DEPARTMENT'])
			||
			in_array($dept_id, $arResult['FILTER_VALUES']['UF_DEPARTMENT'])
		)
			$arResult['DEPARTMENTS'][$dept_id]['USERS'][] = $arUser;
	}
}
?>