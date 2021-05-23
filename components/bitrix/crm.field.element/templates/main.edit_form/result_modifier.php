<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Order\Permissions\Order;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Crm\UserField\DataModifiers;

if(!Loader::includeModule('crm'))
{
	return;
}

global $USER;

CUtil::InitJSCore(['ajax', 'popup']);
\Bitrix\Main\UI\Extension::load(['sidepanel']);

$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$settings = $arParams['userField']['SETTINGS'];
$supportedTypes = DataModifiers\Element::getSupportedTypes($settings); // all entity
$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes, $userPermissions);  // only entity types are allowed for current user
// types are defined in settings

$arResult['PERMISSION_DENIED'] = (empty($arParams['ENTITY_TYPE']) ? true : false);

$arResult['PREFIX'] = (count($supportedTypes) > 1 ? 'Y' : 'N');

if(!empty($arParams['usePrefix']))
{
	$arResult['PREFIX'] = 'Y';
}

$arResult['MULTIPLE'] = $arParams['userField']['MULTIPLE'];

if(!is_array($arResult['value']))
{
	$arResult['value'] = explode(';', $arResult['value']);
}
else
{
	$values = [];
	foreach($arResult['value'] as $value)
	{
		foreach(explode(';', $value) as $val)
		{
			if(!empty($val))
			{
				$values[$val] = $val;
			}
		}
	}
	$arResult['value'] = $values;
}

$arResult['SELECTED_LIST'] = [];

$selectorEntityTypes = [];

$arResult['USE_SYMBOLIC_ID'] = (count($supportedTypes) > 1);

$arResult['LIST_PREFIXES'] = array_flip(ElementType::getEntityTypeNames());

$arResult['SELECTOR_ENTITY_TYPES'] = [
	'DEAL' => 'deals',
	'CONTACT' => 'contacts',
	'COMPANY' => 'companies',
	'LEAD' => 'leads',
	'ORDER' => 'orders'
];

foreach($arResult['value'] as $key => $value)
{
	if(empty($value))
	{
		continue;
	}

	if($arResult['USE_SYMBOLIC_ID'])
	{
		$code = '';
		foreach($arResult['LIST_PREFIXES'] as $type => $prefix)
		{
			if(preg_match('/^' . $prefix . '_(\d+)$/i', $value, $matches))
			{
				$code = $arResult['SELECTOR_ENTITY_TYPES'][$type];
				break;
			}
		}
	}
	else
	{
		foreach($arParams['ENTITY_TYPE'] as $entityType)
		{
			if(!empty($entityType))
			{
				$value = $arResult['LIST_PREFIXES'][$entityType] . '_' . $value;
				$code = $arResult['SELECTOR_ENTITY_TYPES'][$entityType];
				break;
			}
		}
	}

	if(!empty($code))
	{
		$arResult['SELECTED_LIST'][$value] = $code;
	}
}

$arParams['createNewEntity'] = (
	$arParams['createNewEntity']
	&&
	LayoutSettings::getCurrent()->isSliderEnabled()
);

if(!empty($arParams['createNewEntity']))
{
	if(!empty($arResult['ENTITY_TYPE']))
	{
		if(count($arResult['ENTITY_TYPE']) > 1)
		{
			$arResult['PLURAL_CREATION'] = true;
		}
		else
		{
			$arResult['PLURAL_CREATION'] = false;
			$arResult['CURRENT_ENTITY_TYPE'] = current($arResult['ENTITY_TYPE']);
		}
	}

	$arResult['LIST_ENTITY_CREATE_URL'] = [];

	foreach($arResult['ENTITY_TYPE'] as $entityType)
	{
		$arResult['LIST_ENTITY_CREATE_URL'][$entityType] = \CCrmUrlUtil::addUrlParams(
			\CCrmOwnerType::getDetailsUrl(
				CCrmOwnerType::resolveID($entityType),
				0,
				false,
				['ENABLE_SLIDER' => true]
			),
			['init_mode' => 'edit']
		);
	}
}