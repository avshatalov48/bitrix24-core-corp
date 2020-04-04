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

$dbRes = CIBlockSection::GetTreeList(array('IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')));
while ($arRes = $dbRes->Fetch())
{
	$arRes['USERS'] = array();
	$arResult['DEPARTMENTS'][$arRes['ID']] = $arRes;
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

if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
{
	if ($arResult['bAdmin']):
		global $INTRANET_TOOLBAR;
		
		__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

		$current_dep = (intval($_REQUEST['structure_UF_DEPARTMENT']) > 0? '&def_UF_DEPARTMENT='.intval($_REQUEST['structure_UF_DEPARTMENT']) : '');

		$INTRANET_TOOLBAR->AddButton(array(
			'ONCLICK' => $APPLICATION->GetPopupLink(array(
				'URL' => "/bitrix/admin/user_edit.php?lang=".LANGUAGE_ID."&bxpublic=Y&from_module=main".$current_dep,
				'PARAMS' => array(
					'height' => 500,
					'width' => 900,
					'resize' => false
				)
			)),
			"TEXT" => GetMessage('INTR_ABSC_TPL_ADD_ENTRY'),
			"ICON" => 'add',
			"SORT" => 1000,
		));

		if ($USER->CanDoOperation('edit_all_users'))
		{
			$INTRANET_TOOLBAR->AddButton(array(
				'HREF' => "/bitrix/admin/user_import.php?lang=".LANGUAGE_ID,
				"TEXT" => GetMessage('INTR_ABSC_TPL_IMPORT'),
				'ICON' => 'import-users',
				"SORT" => 1100,
			));
		}
		
		$INTRANET_TOOLBAR->AddButton(array(
			'HREF' => "/bitrix/admin/user_admin.php?lang=".LANGUAGE_ID,
			"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES'),
			'ICON' => 'settings',
			"SORT" => 1100,
		));
	endif;
}
?>