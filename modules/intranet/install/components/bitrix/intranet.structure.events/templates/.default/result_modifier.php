<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['USER_PROP'] = array();

$arParams['SHOW_FILTER'] = $arParams['SHOW_FILTER'] == 'N' ? 'N' : 'Y';

if ($arParams['SHOW_FILTER'] == 'Y')
{
	$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, LANGUAGE_ID);
	$arResult['UF_DEPARTMENT_field'] = $arUserFields['UF_DEPARTMENT'];
	$arResult['UF_DEPARTMENT_field']['FIELD_NAME'] = 'department';
	$arResult['UF_DEPARTMENT_field']['MULTIPLE'] = 'N';
	$arResult['UF_DEPARTMENT_field']['SETTINGS']['LIST_HEIGHT'] = 1;
}

$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arResult['USER_PROP'][$val["FIELD_NAME"]] = (strLen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

if ($arParams['bAdmin']):

	global $INTRANET_TOOLBAR;
	
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$INTRANET_TOOLBAR->AddButton(array(
		'ONCLICK' => $APPLICATION->GetPopupLink(array(
			'URL' => "/bitrix/admin/iblock_element_edit.php?type=".$arParams['IBLOCK_TYPE']."&lang=".LANGUAGE_ID."&IBLOCK_ID=". $arParams['IBLOCK_ID']."&bxpublic=Y&from_module=iblock",
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
		'HREF' => "/bitrix/admin/iblock_element_admin.php?type=".$arParams['IBLOCK_TYPE']."&lang=".LANGUAGE_ID."&IBLOCK_ID=".$arParams['IBLOCK_ID'],
		"TEXT" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES'),
		"TITLE" => GetMessage('INTR_ABSC_TPL_EDIT_ENTRIES_TITLE'),
		'ICON' => 'settings',
		"SORT" => 1100,
	));
endif;
?>