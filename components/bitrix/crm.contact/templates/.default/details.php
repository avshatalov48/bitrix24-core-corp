<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$categoryId = 0;
if ($arResult['VARIABLES']['contact_id'] > 0)
{
	$categoryId = (int)Container::getInstance()
		->getFactory(CCrmOwnerType::Contact)
		->getItemCategoryId($arResult['VARIABLES']['contact_id'])
	;
}
elseif (isset($_REQUEST['category_id']))
{
	$categoryId = (int)$_REQUEST['category_id'];
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.details.frame',
	'',
	[
		'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
		'ENTITY_ID' => $arResult['VARIABLES']['contact_id'],
		'ENABLE_TITLE_EDIT' => false,
		'EXTRAS' => ['CATEGORY_ID' => $categoryId],
	]
);
