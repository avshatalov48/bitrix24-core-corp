<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
Loc::loadMessages(__FILE__);

$actionList = array(
	'SYSTEM' => array(
		array(
			'popup' => true,
			'id' => 'edit',
			'text' => $arResult['PERM_CAN_EDIT'] ? Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_EDIT') : Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_VIEW'),
			'url_template' => $arParams['PATH_TO_WEB_FORM_EDIT'],
			'url_replace' => $arParams['PATH_TO_WEB_FORM_EDIT'],
		),
	),
	'USER' => array(
		array(
			'popup' => true,
			'id' => 'edit',
			'text' => $arResult['PERM_CAN_EDIT'] ? Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_EDIT') : Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_VIEW'),
			'url_template' => $arParams['PATH_TO_WEB_FORM_EDIT'],
			'url_replace' => $arParams['PATH_TO_WEB_FORM_EDIT'],
		),
	)
);

if($arResult['PERM_CAN_EDIT'])
{
	$actionCopyAs = array(
		'popup' => true,
		'id' => 'copy',
		'text' => Loc::getMessage('CRM_BUTTON_LIST_ACTIONS_COPY'),
		'url' => $arParams['PATH_TO_WEB_FORM_EDIT']
	);
	$actionList['SYSTEM'][] = $actionCopyAs;
	$actionList['USER'][] = $actionCopyAs;
}

$viewList = array();
$viewTypeList = array_keys($viewList);
$userOptionViewType = 'site_button_list_view';
$userViewTypes = \CUserOptions::GetOption('crm', $userOptionViewType, array());

$debugVarOneItemAsSystemInited = false;
$arResult['ITEMS_BY_IS_SYSTEM'] = array(
	'N' => array(
		'NAME' => Loc::getMessage('CRM_BUTTON_LIST_FORMS_MINE'),
		'ITEMS' => array()
	),
	'Y' => array(
		'NAME' => Loc::getMessage('CRM_BUTTON_LIST_FORMS_PRESET'),
		'ITEMS' => array()
	)
);
foreach($arResult['ITEMS'] as $item)
{
	$item['IS_SYSTEM'] = $item['IS_SYSTEM'] == 'Y' ? 'Y' : 'N';

	$viewClassName = '';
	$itemViewList = $viewList;
	$item['VIEW_TYPE'] = isset($userViewTypes[$item['ID']]) ? $userViewTypes[$item['ID']] : null;
	$item['VIEW_TYPE'] = in_array($item['VIEW_TYPE'], $viewTypeList) ? $item['VIEW_TYPE'] : $viewTypeList[0];
	foreach($itemViewList as $viewType => $view)
	{
		$itemViewList[$viewType]['VALUE'] = $item['SUMMARY_CONVERSION_' . $viewType];
		$itemViewList[$viewType]['SELECTED'] = $item['VIEW_TYPE'] == $viewType;
		if($viewType != $item['VIEW_TYPE'])
		{
			continue;
		}
		$itemViewList[$viewType]['SELECTED'] = true;
		$viewClassName = $view['CLASS_NAME'];
	}

	$item['viewClassName'] = $viewClassName;
	$item['itemViewList'] = $itemViewList;

	$arResult['ITEMS_BY_IS_SYSTEM'][$item['IS_SYSTEM']]['ITEMS'][] = $item;
}
if(count($arResult['ITEMS_BY_IS_SYSTEM']['N']['ITEMS']) == 0)
{
	unset($arResult['ITEMS_BY_IS_SYSTEM']['N']);
}
if(count($arResult['ITEMS_BY_IS_SYSTEM']['Y']['ITEMS']) == 0)
{
	unset($arResult['ITEMS_BY_IS_SYSTEM']['Y']);
}

$arResult['HIDE_DESC'] = isset($userViewTypes['hide-desc']) && $userViewTypes['hide-desc'] == 'Y';

$arResult['actionList'] = $actionList;
$arResult['viewList'] = $viewList;
$arResult['userOptionViewType'] = $userOptionViewType;