<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CDiskExternalLinkComponent $component */

$APPLICATION->includeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:disk.flipchart.editor',
		'POPUP_COMPONENT_PARAMS' => [
			'DOCUMENT_SESSION' => $arResult['DOCUMENT_SESSION'],
			'DOCUMENT_URL' => $arResult['LINK_TO_DOWNLOAD'],
			'STORAGE_ID' => $arResult['STORAGE_ID'],
			'USER_ID' => $arResult['USER_ID'],
			'USERNAME' => $arResult['USER_NAME'],
			'AVATAR_URL' => $arResult['USER_AVATAR'],
			'CAN_EDIT_BOARD' => true,
			'SHOW_TEMPLATES_MODAL' => false,
			'EXTERNAL_LINK_MODE' => true,
		],
		'PLAIN_VIEW' => true,
		'IFRAME_MODE' => true,
		'PREVENT_LOADING_WITHOUT_IFRAME' => false,
		'USE_PADDING' => false,
	]
);