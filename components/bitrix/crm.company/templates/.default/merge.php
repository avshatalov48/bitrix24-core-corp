<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$APPLICATION->SetTitle(GetMessage('CRM_COMPANY_MERGE_PAGE_TITLE'));
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.entity.merger',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'GUID' => 'company_merger',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_IDS' => $arResult['VARIABLES']['company_ids'],
			'PATH_TO_DEDUPE_LIST' => '/crm/company/dedupelist/',
			'PATH_TO_ENTITY_LIST' => '/crm/company/',
			'PATH_TO_EDITOR' => '/bitrix/components/bitrix/crm.company.details/ajax.php',
			'HEADER_TEMPLATE' => GetMessage('CRM_COMPANY_MERGE_HEADER_TEMPLATE'),
			'RESULT_LEGEND' => GetMessage('CRM_COMPANY_MERGE_RESULT_LEGEND'),
		],
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/crm/company/'
	]
);
