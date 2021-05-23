<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'tactile',
	array(
		'FORM_ID' => $arParams['~FORM_ID'],
		'THEME_GRID_ID' => $arParams['~GRID_ID'],
		'TABS' => $arParams['~TABS'],
		'TABS_META' => isset($arParams['~TABS_META']) ? $arParams['~TABS_META'] : null,
		'AVAILABLE_FIELDS' => isset($arParams['~AVAILABLE_FIELDS']) ? $arParams['~AVAILABLE_FIELDS'] : null,
		'MODE' => isset($arParams['~MODE']) ? $arParams['~MODE'] : 'EDIT',
		'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
		'BUTTONS' => $arParams['~BUTTONS'],
		'DATA' => $arParams['~DATA'],
		'TITLE' => isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '',
		'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
		'ENABLE_USER_FIELD_CREATION' => isset($arParams['~ENABLE_USER_FIELD_CREATION']) ? $arParams['~ENABLE_USER_FIELD_CREATION'] : 'Y',
		'ENABLE_SECTION_EDIT' => isset($arParams['~ENABLE_SECTION_EDIT']) ? $arParams['~ENABLE_SECTION_EDIT'] : 'Y',
		'ENABLE_SECTION_CREATION' => isset($arParams['~ENABLE_SECTION_CREATION']) ? $arParams['~ENABLE_SECTION_CREATION'] : 'Y',
		'SETTINGS' => isset($arParams['~SETTINGS']) ? $arParams['~SETTINGS'] : null,
		'USER_FIELD_ENTITY_ID' => isset($arParams['~USER_FIELD_ENTITY_ID']) ? $arParams['~USER_FIELD_ENTITY_ID'] : '',
		'USER_FIELD_SERVICE_URL' => isset($arParams['~USER_FIELD_SERVICE_URL']) ? $arParams['~USER_FIELD_SERVICE_URL'] : '',
		'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y',
		'SHOW_FORM_TAG' => isset($arParams['~SHOW_FORM_TAG']) ? $arParams['~SHOW_FORM_TAG'] : 'Y',
		'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> isset($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) ?
			strval($arParams['CUSTOM_FORM_SETTINGS_COMPONENT_PATH']) : null,
		'ENABLE_IN_SHORT_LIST_OPTION' => isset($arParams['~ENABLE_IN_SHORT_LIST_OPTION']) ? $arParams['~ENABLE_IN_SHORT_LIST_OPTION'] : 'N',
		'IS_MODAL' => isset($arParams['~IS_MODAL']) ? $arParams['~IS_MODAL'] : 'N',
		'PREFIX' => isset($arParams['~PREFIX']) ? $arParams['~PREFIX'] : ''
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>