<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\Types\ElementType;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Crm\UserField\DataModifiers;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions\Order;
use Bitrix\Main\Text\HtmlFilter;

if(!Loader::includeModule('crm'))
{
	return;
}

global $USER, $APPLICATION;

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$supportedTypes = []; // all entity types are defined in settings
$arParams['ENTITY_TYPE'] = []; // only entity types are allowed for current user

$request = Context::getCurrent()->getRequest();
$settings = $request->getValues();
unset($settings['mobile_action']);

$arResult['value'] = [];

$supportedTypes = DataModifiers\Element::getSupportedTypes($settings); // all entity
$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes, $userPermissions);  // only entity types are allowed for current user

$arResult['PERMISSION_DENIED'] = (empty($arParams['ENTITY_TYPE']) ? true : false);

$arResult['PREFIX'] = (count($supportedTypes) > 1 ? 'Y' : 'N');

$arResult['SELECTED'] = [];
$arResult['SELECTED_LIST'] = [];

$selectorEntityTypes = [];

$arResult['USE_SYMBOLIC_ID'] = (count($arParams['ENTITY_TYPE']) > 1);

$arResult['LIST_PREFIXES'] = array_flip(ElementType::getEntityTypeNames());

$arResult['SELECTOR_ENTITY_TYPES'] = [
	'DEAL' => 'deals',
	'CONTACT' => 'contacts',
	'COMPANY' => 'companies',
	'LEAD' => 'leads'
];

$arResult['ELEMENT'] = array();
$arResult['ENTITY_TYPE'] = array();

// last 50 entity
DataModifiers\Element::setLeads($arResult, $arParams, $userPermissions);
DataModifiers\Element::setContacts($arResult, $arParams, $userPermissions);
DataModifiers\Element::setCompanies($arResult, $arParams, $userPermissions);
DataModifiers\Element::setDeals($arResult, $arParams, $userPermissions);

if(method_exists(DataModifiers\Element::class, 'setDynamics'))
{
	DataModifiers\Element::setDynamics($arResult, $arParams, $userPermissions);
}

$names = [];
$elements = [];
foreach($arResult['ENTITY_TYPE'] as $entityTypeId => $type)
{
	if(
		method_exists(\CCrmOwnerType::class, 'isPossibleDynamicTypeId')
		&& \CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
	)
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$typeName = HtmlFilter::encode($factory->getEntityDescription());
	}
	else
	{
		$typeName = Loc::getMessage(
			'CRM_ENTITY_TYPE_' . ElementType::getLongEntityType($arResult['LIST_PREFIXES'][mb_strtoupper($type)])
		);
	}

	$names[$type] = $typeName;

	foreach($arResult['ELEMENT'] as $element)
	{
		if($element['type'] === $type)
		{
			$item = [
				'ID' => $element['id'],
				'NAME' => $element['title'],
				'TAGS' => $element['desc'],
				'LINK' => $element['url'],
				'TYPE' => $element['type']
			];

			$elements[$type][] = $item;
		}
	}
}

return [
	'data' => $elements,
	'names' => $names
];
