<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true){
	die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arResult
 */

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');

$requisiteId = isset($arResult['VARIABLES']['requisite_id'])
	? (int)$arResult['VARIABLES']['requisite_id']
	: 0
;

$APPLICATION->IncludeComponent(
	'bitrix:crm.requisite.details.slider',
	'',
	[
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'REQUISITE_ID' => $requisiteId,
	],
);
