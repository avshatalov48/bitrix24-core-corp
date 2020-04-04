<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arParams['bCache'])
{
	$cache_dir = '/'.SITE_ID.$this->__component->GetRelativePath().'/'.$this->GetName();
	$cache_id = $this->GetFile().'|'.$arParams['NUM_USERS'].'|'.$arParams['IBLOCK_ID'];//.'|'.$USER->GetGroups();
	$obCache = new CPHPCache();
}

$IBLOCK_PERMISSION = CIBlock::GetPermission($arParams['IBLOCK_ID']);
$arParams['bAdmin'] = $IBLOCK_PERMISSION >= 'U';

/*
$arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] == 'N' ? 'N' : 'Y';

if ($arParams['SHOW_FILTER'] == 'Y')
{
	$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
	$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
	$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;
}
*/

$bLoadDepartments = is_array($arParams['USER_PROPERTY']) && in_array('UF_DEPARTMENT', $arParams['USER_PROPERTY']);

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
			$arResult['USER_PROP'][$val["FIELD_NAME"]] = (strLen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
		}
	}
}

if ($arResult['bUsersCached'])
	$strUserIDs = '';

if ($arParams['bCache'])
{
	$obCache->StartDataCache();
	global $CACHE_MANAGER;
	$CACHE_MANAGER->StartTagCache($cache_dir);
}

if (is_array($arResult['USERS']))
{
	foreach ($arResult['USERS'] as $key => $arUser)
	{
		if ($arResult['bUsersCached'])
			$strUserIDs .= ($strUserIDs ? '|' : '').$arUser['ID'];
		
		if (!is_array($arCacheData[$arUser['ID']]))
			$arCacheData[$arUser['ID']] = array();

		if (!$arResult['bUsersCached'])
			$arUser['IS_ONLINE'] = CIntranetUtils::IsOnline($arUser['LAST_ACTIVITY_DATE']);

		$arUser['IS_BIRTHDAY'] = CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY']);
		
		$arUser['IS_FEATURED'] = true;
		
		if ($arUser['PERSONAL_PHOTO'])
		{
			$arImage = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100);
			$arUser['PERSONAL_PHOTO'] = $arImage['IMG'];
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
}

if ($arParams['bCache'])
{
	$CACHE_MANAGER->EndTagCache();
	$obCache->EndDataCache(array(
		'TEMPLATE_DATA' => $arCacheData,
		'USER_PROP' => $arResult['USER_PROP'],
	));
}

if ($arResult['bUsersCached'] && strlen($strUserIDs) > 0)
{
	$dbRes = CUser::GetList($by='id', $order='asc', array('ID' => $strUserIDs, 'LAST_ACTIVITY' => 120));
	while ($arRes = $dbRes->Fetch())
	{
		$arResult['USERS'][$arRes['ID']]['IS_ONLINE'] = true;
	}
	unset($dbRes);
}

foreach ($arResult['USERS'] as $USER_ID => $arUser)
{
	$arResult['USERS'][$USER_ID]['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($USER_ID);
}

if ($arParams['bAdmin']):
	
	global $INTRANET_TOOLBAR;
	
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$INTRANET_TOOLBAR->AddButton(array(
		'ONCLICK' => $APPLICATION->GetPopupLink(array(
			'URL' => "/bitrix/admin/iblock_element_edit.php?type=".CUtil::JSEscape($arParams['IBLOCK_TYPE'])."&lang=".LANGUAGE_ID."&IBLOCK_ID=".$arParams['IBLOCK_ID']."&bxpublic=Y&from_module=iblock",
			'PARAMS' => array(
				'height' => 500,
				'width' => 700,
				'resize' => false
			)
		)),
		"TEXT" => GetMessage('INTR_ABSC_TPL_ADD_ENTRY'),
		"ICON" => 'add',
		"SORT" => 1000,
	));

	$INTRANET_TOOLBAR->AddButton(array(
		'HREF' => "/bitrix/admin/iblock_element_admin.php?type=".htmlspecialcharsbx($arParams['IBLOCK_TYPE'])."&lang=".LANGUAGE_ID."&IBLOCK_ID=".$arParams['IBLOCK_ID'],
		"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES'),
		'ICON' => 'settings',
		"SORT" => 1100,
	));
endif;
?>