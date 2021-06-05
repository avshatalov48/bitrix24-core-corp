<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->__component->GetRelativePath().'/'.$this->GetName();
	$cache_id = $this->GetFile().'|'.$arParams['NUM_USERS'];//.'|'.$USER->GetGroups();
	$obCache = new CPHPCache();
}

$arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] == 'N' ? 'N' : 'Y';

if (!$arParams['bShowFilter'])
	$arParams['SHOW_FILTER'] = 'N';

$bLoadDepartments = in_array('UF_DEPARTMENT', $arParams['USER_PROPERTY']);

if ($arParams['bCache'] && $obCache->InitCache($arParams['CACHE_TIME'], $cache_id, $cache_dir))
{
	$vars = $obCache->GetVars();
	$arCacheData = $vars['TEMPLATE_DATA'];
	$arResult['USER_PROP'] = $vars['USER_PROP'];
}
else
{
	$arCacheData = array();

	$arResult['USER_PROP'] = array();
	$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);

	if (!empty($arRes))
	{
		foreach ($arRes as $key => $val)
		{
			$arResult['USER_PROP'][$val["FIELD_NAME"]] = ($val["EDIT_FORM_LABEL"] <> '' ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
		}
	}
}

if ($arParams['SHOW_FILTER'] == 'Y')
{
	$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
	$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
	$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;
}

if ($arResult['bUsersCached'])
	$strUserIDs = '';

if ($arParams['bCache'])
{
	$obCache->StartDataCache();
	global $CACHE_MANAGER;
	$CACHE_MANAGER->StartTagCache($cache_dir);
}

foreach ($arResult['USERS'] as $key => $arUser)
{
	if ($arResult['bUsersCached'])
		$strUserIDs .= ($strUserIDs ? '|' : '').$arUser['ID'];

	if (!is_array($arCacheData[$arUser['ID']]))
		$arCacheData[$arUser['ID']] = array();

	$arUser['IS_ONLINE'] = $arResult['bUsersCached'] ? false : CIntranetUtils::IsOnline($arUser['LAST_ACTIVITY_DATE']);

	$arUser['IS_BIRTHDAY'] = CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY']);
	
	if (array_key_exists('IS_ABSENT', $arCacheData[$arUser['ID']]))
	{
		$arUser['IS_ABSENT'] = $arCacheData[$arUser['ID']]['IS_ABSENT'];
	}
	else
	{
		$arUser['IS_ABSENT'] = $arCacheData[$arUser['ID']]['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arUser['ID']);
	}

	if (array_key_exists('IS_FEATURED', $arCacheData[$arUser['ID']]))
	{
		$arUser['IS_FEATURED'] = $arCacheData[$arUser['ID']]['IS_FEATURED'];
	}
	else
	{
		$arUser['IS_FEATURED'] = $arCacheData[$arUser['ID']]['IS_FEATURED'] = CIntranetUtils::IsUserHonoured($arUser['ID']);
	}
	
	
	if ($arUser['PERSONAL_PHOTO'])
	{
		$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100);
		$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
		//$arUser['PERSONAL_PHOTO'] = CFile::ShowImage($arUser['PERSONAL_PHOTO'], 100, 100);
	}

	if ($bLoadDepartments && is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) > 0)
	{
		if (array_key_exists('UF_DEPARTMENT', $arCacheData[$arUser['ID']]))
		{
			$arUser['UF_DEPARTMENT'] = $arCacheData[$arUser['ID']]['UF_DEPARTMENT'];
		}
		else
		{
			$arUser['UF_DEPARTMENT'] = $arCacheData[$arUser['ID']]['UF_DEPARTMENT'] = CIntranetUtils::GetDepartmentsData($arUser['UF_DEPARTMENT']);
		}
	}
	
	$arResult['USERS'][$key] = $arUser;
}

if ($arParams['bCache'])
{
	$CACHE_MANAGER->EndTagCache();
	$obCache->EndDataCache(array(
		'TEMPLATE_DATA' => $arCacheData,
		'USER_PROP' => $arResult['USER_PROP'],
	));
}

if ($arResult['bUsersCached'] && $strUserIDs <> '')
{
	$dbRes = CUser::GetList('id', 'asc', array('ID' => $strUserIDs, 'LAST_ACTIVITY' => 120));
	while ($arRes = $dbRes->Fetch())
	{
		$arResult['USERS'][$arRes['ID']]['IS_ONLINE'] = true;
	}
	unset($dbRes);
}
?>