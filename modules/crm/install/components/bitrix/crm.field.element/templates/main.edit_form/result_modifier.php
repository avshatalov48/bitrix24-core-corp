<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Crm\UserField\DataModifiers;
use Bitrix\Main\Text\HtmlFilter;

/**
 * @var array $arResult
 * @var array $arParams
 */

if(!Loader::includeModule('crm'))
{
	return;
}

global $USER;

CUtil::InitJSCore(['ajax', 'popup']);
\Bitrix\Main\UI\Extension::load(['sidepanel']);

$settings = $arParams['userField']['SETTINGS'];
$supportedTypes = DataModifiers\Element::getSupportedTypes($settings); // all entity
$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes);  // only entity types are allowed for current user
// types are defined in settings

$arResult['PERMISSION_DENIED'] = empty($arParams['ENTITY_TYPE']);

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

$arResult['SELECTOR_ENTITY_TYPES'] = ElementType::getSelectorEntityTypes();

foreach($arResult['value'] as $key => $value)
{
	if(empty($value))
	{
		continue;
	}

	if($arResult['USE_SYMBOLIC_ID'])
	{
		[$type, $entityId] = explode('_', $value);
		if (empty($entityId) && (int)$type > 0)
		{
			$entityId = $type;
			$entityTypeName = reset($supportedTypes);
			$value = \CCrmOwnerTypeAbbr::ResolveByTypeName($entityTypeName) . '_' . $entityId;
		}
		else
		{
			$entityTypeName = ElementType::getLongEntityType($type);
		}
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		$code = '';
		if (isset($arResult['LIST_PREFIXES'][$entityTypeName]))
		{
			$code = $arResult['SELECTOR_ENTITY_TYPES'][$entityTypeName];
		}
		elseif (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$code = $arResult['SELECTOR_ENTITY_TYPES'][\CCrmOwnerType::CommonDynamicName] . '_' . $entityTypeId;
		}
	}
	elseif(preg_match('/(\d+)$/i', $value, $matches))
	{
		foreach($arParams['ENTITY_TYPE'] as $entityType)
		{
			if(!empty($entityType))
			{
				$entityTypeId = \CCrmOwnerType::ResolveId($entityType);
				$value = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId) . '_' . $matches[0];
				$code = (
				\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
					? $arResult['SELECTOR_ENTITY_TYPES'][\CCrmOwnerType::CommonDynamicName] . '_' . $entityTypeId
					: $arResult['SELECTOR_ENTITY_TYPES'][$entityType]
				);

				break;
			}
		}
	}

	if(!empty($code))
	{
		$arResult['SELECTED_LIST'][$value] = $code;
	}
}

$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->load([
	'isLoadStages' => false,
]);

$types = $typesMap->getTypes();
foreach($types as $type)
{
	$code = $arResult['SELECTOR_ENTITY_TYPES'][\CCrmOwnerType::CommonDynamicName] . '_' . $type->getEntityTypeId();
	$arResult['DYNAMIC_TYPE_TITLES'][mb_strtoupper($code)] = HtmlFilter::encode($type->getTitle());
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
