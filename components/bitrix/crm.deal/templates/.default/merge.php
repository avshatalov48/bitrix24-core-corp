<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage('CRM_DEAL_MERGE_PAGE_TITLE'));
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.entity.merger',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => 'deal_merger',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
			'ENTITY_IDS' => $arResult['VARIABLES']['deal_ids'],
			'PATH_TO_DEDUPE_LIST' => '',
			'PATH_TO_ENTITY_LIST' => '/crm/deal/',
			'PATH_TO_EDITOR' => '/bitrix/components/bitrix/crm.deal.details/ajax.php',
			'HEADER_TEMPLATE' => GetMessage('CRM_DEAL_MERGE_HEADER_TEMPLATE'),
			'RESULT_LEGEND' => GetMessage('CRM_DEAL_MERGE_RESULT_LEGEND'),
		],
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/crm/deal/'
	]
);
