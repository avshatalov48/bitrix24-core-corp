<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details.frame',
	'',
	array(
		'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
		'ENTITY_ID' => $arResult['VARIABLES']['deal_id'],
		'ENABLE_TITLE_EDIT' => true,
		'EXTRAS' => array(
			'DEAL_CATEGORY_ID' => isset($arResult['VARIABLES']['category_id'])
				? $arResult['VARIABLES']['category_id'] : -1
		)

	)
);