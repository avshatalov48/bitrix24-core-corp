<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_DEDUPE_LIST_PAGE_TITLE'));
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.dedupe.grid',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => 'contact_dedupe_list',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact
		],
		'USE_PADDING' => false
	]
);