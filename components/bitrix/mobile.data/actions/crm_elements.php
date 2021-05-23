<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\UserField\Types\ElementType;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Crm\UserField\DataModifiers;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order\Permissions\Order;

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

if(!empty($arParams['usePrefix']))
{
	$arResult['PREFIX'] = 'Y';
}

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

	if($arResult['PREFIX'] === 'Y')
	{
		$arResult['SELECTED'][$value] = $value;

	}
	else
	{
		// Try to get raw entity ID
		$ary = explode('_', $value);
		if(count($ary) > 1)
		{
			$value = $ary[1];
		}

		$arResult['SELECTED'][$value] = $value;
	}
}

$arResult['ELEMENT'] = array();
$arResult['ENTITY_TYPE'] = array();

// last 50 entity
DataModifiers\Element::setLeads($arResult, $arParams, $userPermissions);
DataModifiers\Element::setContacts($arResult, $arParams, $userPermissions);
DataModifiers\Element::setCompanies($arResult, $arParams, $userPermissions);
DataModifiers\Element::setDeals($arResult, $arParams, $userPermissions);

if(!empty($arResult['SELECTED']))
{
	foreach($arResult['SELECTED'] as $value)
	{
		if(is_numeric($value))
		{
			$selected[$arParams['ENTITY_TYPE'][0]][] = $value;
		}
		else
		{
			$ar = explode('_', $value);
			$selected[CUserTypeCrm::GetLongEntityType($ar[0])][] = (int)$ar[1];
		}
	}

	DataModifiers\Element::setResultElements($arResult, $arParams, $settings, $selected);
	DataModifiers\Element::setContactElements($arResult, $arParams, $settings, $selected);
	DataModifiers\Element::setCompanyElements($arResult, $arParams, $settings, $selected);
	DataModifiers\Element::setDealElements($arResult, $arParams, $settings, $selected);
}

$names = [];
$elements = [];
foreach($arResult['ENTITY_TYPE'] as $type)
{

	$typeName = Loc::getMessage(
		'CRM_ENTITY_TYPE_' . ElementType::getLongEntityType($arResult['LIST_PREFIXES'][mb_strtoupper($type)])
	);
	if(SITE_CHARSET !== 'utf-8')
	{
		$typeName = $APPLICATION->ConvertCharsetArray($typeName, SITE_CHARSET, 'utf-8');
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

			if(SITE_CHARSET !== 'utf-8')
			{
				$item = $APPLICATION->ConvertCharsetArray($item, SITE_CHARSET, 'utf-8');
			}

			$elements[$type][] = $item;
		}
	}
}

return [
	'data' => $elements,
	'names' => $names
];