<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.entity.details.frame',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_TYPE_ID' => CCrmOwnerType::Order,
			'ENTITY_ID' => $arResult['orderId'],
			'ENABLE_TITLE_EDIT' => false,
			'DISABLE_TOP_MENU' => 'Y',
			'EXTRAS' => $arResult['extras'],
		],
	]
);