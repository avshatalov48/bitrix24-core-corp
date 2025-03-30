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
		'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'EXTERNAL_LINK_MODE' => true,
			'SHOW_BUTTON_OPEN_NEW_WINDOW' => false,
			'LINK_TO_EDIT' => $arResult['LINK_TO_EDIT'] ?? '',
			'LINK_TO_DOWNLOAD' => $arResult['LINK_TO_DOWNLOAD'] ?? '',
			'DOCUMENT_SESSION' => $arResult['DOCUMENT_SESSION'],
		],
		'PLAIN_VIEW' => true,
		'IFRAME_MODE' => true,
		'PREVENT_LOADING_WITHOUT_IFRAME' => false,
		'USE_PADDING' => false,
	]
);