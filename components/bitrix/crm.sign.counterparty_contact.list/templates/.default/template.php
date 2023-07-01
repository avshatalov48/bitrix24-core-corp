<?php

use Bitrix\Main\UI\Extension;
use Bitrix\Crm\Component\EntityList\GridId;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global \CMain $APPLICATION */
/** @var array $arResult */
/** @var \CatalogStoreDocumentControllerComponent $component */
/** @var \CBitrixComponentTemplate $this */

global $APPLICATION;

Extension::load([
	'sidepanel',
]);

$menuItems = $arResult['MENU_ITEMS'];

foreach ($menuItems as &$menuItem)
{
	$menuItem['IS_ACTIVE'] = 'sign_contacts' === $menuItem['ID'];
}
// top menu insert

$APPLICATION->clearViewContent('above_pagetitle');

$this->setViewTarget('above_pagetitle', 100);
$APPLICATION->includeComponent(
	'bitrix:main.interface.buttons',
	'',
	array(
		'ID' => 'sign',
		'ITEMS' => $menuItems
	)
);
$this->endViewTarget();

if (!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled(CCrmOwnerType::Contact))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.counter.panel',
		'',
		[
			'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
			'EXTRAS' => [
				'CATEGORY_ID' => $arResult['CATEGORY_ID'],
			],
			'PATH_TO_ENTITY_LIST' => $arResult['PATH_TO_LIST'],
		]
	);

	$APPLICATION->ShowViewContent('crm-grid-filter');

	$APPLICATION->IncludeComponent(
		'bitrix:crm.contact.menu',
		'',
		[
			'CATEGORY_ID' => $arResult['CATEGORY_ID'],
			'TYPE' => 'list',
			'IN_SLIDER' => $component->isIframeMode() ? 'Y' : 'N',
		],
		$component
	);

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:crm.contact.list',
			'POPUP_COMPONENT_PARAMS' => [
				'CATEGORY_ID' => $arResult['CATEGORY_ID'],
				'GRID_ID_SUFFIX' => (new GridId(CCrmOwnerType::Contact))
					->getDefaultSuffix($arResult['CATEGORY_ID']),
				'PATH_TO_CONTACT_LIST' => $arResult['PATH_TO_LIST'],
				'CRM_CUSTOM_PAGE_TITLE' => Loc::getMessage('CRM_SIGN_COUNTERPARTY_CONTACT_LIST_TITLE'),
			],
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			'USE_UI_TOOLBAR' => 'Y',
		]
	);
}
