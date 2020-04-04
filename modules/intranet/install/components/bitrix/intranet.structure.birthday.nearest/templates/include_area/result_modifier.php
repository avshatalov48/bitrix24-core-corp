<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);

if ($arParams['bShowFilter'])
{
	$dbCurrentUser = CUser::GetByID($GLOBALS['USER']->GetID());
	$arResult['CURRENT_USER'] = $dbCurrentUser->Fetch();
	if ($arParams['bShowFilter'] = !!($arResult['CURRENT_USER']['UF_DEPARTMENT']))
	{
		$arResult['CURRENT_USER']['DEPARTMENT_TOP'] = CIntranetUtils::GetIBlockTopSection($arResult['CURRENT_USER']['UF_DEPARTMENT']);
		if (intval($arResult['DEPARTMENT']) == $arResult['CURRENT_USER']['DEPARTMENT_TOP'])
			$arResult['ONLY_MINE'] = 'Y';
	}
}

foreach ($arResult['USERS'] as $key => $arUser)
{
	if ($arUser['PERSONAL_PHOTO'])
	{
		$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 50);
		$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
	}

	$arResult['USERS'][$key] = $arUser;
}

$arResult["arUserField"] = array();
$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arResult["arUserField"][$key] = $val;
	}
}
?>