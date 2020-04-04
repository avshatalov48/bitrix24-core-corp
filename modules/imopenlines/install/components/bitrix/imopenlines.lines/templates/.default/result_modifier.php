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
			'text' => Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_VIEW'),
			'url_template' => $arResult['PATH_TO_EDIT'],
			'url_replace' => $arResult['PATH_TO_EDIT'],
		),
	),
	'USER' => array(
		array(
			'popup' => true,
			'id' => 'edit',
			'text' => $arResult['PERM_CAN_EDIT'] ? Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_EDIT') : Loc::getMessage('OL_COMPONENT_LIST_ACTIONS_VIEW'),
			'url_template' => $arResult['PATH_TO_EDIT'],
			'url_replace' => $arResult['PATH_TO_EDIT'],
		),
	)
);

$userOptionViewType = 'site_ol_list_view';
$userViewTypes = \CUserOptions::GetOption('imopenlines', $userOptionViewType, array());
$arResult['HIDE_DESC'] = isset($userViewTypes['hide-desc']) && $userViewTypes['hide-desc'] == 'Y';

$arResult['actionList'] = $actionList;
$arResult['userOptionViewType'] = $userOptionViewType;

$arResult['ICON_MAP'] = \Bitrix\ImConnector\Connector::getIconClassMap();