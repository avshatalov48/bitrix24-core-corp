<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_MERGE_PAGE_TITLE'));
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.entity.merger',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => 'contact_merger',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_IDS' => $arResult['VARIABLES']['contact_ids'],
			'PATH_TO_DEDUPE_LIST' => '/crm/contact/dedupelist/',
			'PATH_TO_ENTITY_LIST' => '/crm/contact/',
			'PATH_TO_EDITOR' => '/bitrix/components/bitrix/crm.contact.details/ajax.php',
			'HEADER_TEMPLATE' => GetMessage('CRM_CONTACT_MERGE_HEADER_TEMPLATE'),
			'RESULT_LEGEND' => GetMessage('CRM_CONTACT_MERGE_RESULT_LEGEND'),
		],
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/crm/contact/'
	]
);
