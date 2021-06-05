<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Crm\UserField\DataModifiers;

if(!Loader::includeModule('crm'))
{
	return;
}

if(is_array($arResult['value']) && count($arResult['value']))
{
	$arParams['ENTITY_TYPE'] = DataModifiers\Element::getSupportedTypes(
		$arParams['userField']['SETTINGS']
	);

	$arParams['PREFIX'] = false;
	if(count($arParams['ENTITY_TYPE']) > 1)
	{
		$arParams['PREFIX'] = true;
	}
	if(!empty($arParams['usePrefix']))
	{
		$arResult['PREFIX'] = 'Y';
	}

	$values = [];
	foreach($arResult['value'] as $value)
	{
		if(is_numeric($value))
		{
			$values[$arParams['ENTITY_TYPE'][0]][] = $value;
		}
		else
		{
			$ar = explode('_', $value);
			$values[ElementType::getLongEntityType($ar[0])][] = (int)$ar[1];
		}
	}

	$arResult['value'] = [];

	$settings = $arParams['userField']['SETTINGS'];
	$supportedTypes = DataModifiers\Element::getSupportedTypes($settings);
	$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes);  // only entity types are allowed for current user

	$arResult['PREFIX'] = (count($supportedTypes) > 1 ? 'Y' : 'N');

	if(!empty($arParams['usePrefix']))
	{
		$arResult['PREFIX'] = 'Y';
	}

	$arResult['MULTIPLE'] = $arParams['userField']['MULTIPLE'];

	if(!is_array($arResult['userField']['VALUE']))
	{
		$arResult['value'] = explode(';', $arResult['userField']['VALUE']);
	}
	else
	{
		$values = [];
		foreach($arResult['userField']['VALUE'] as $value)
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

	foreach($arResult['value'] as $key => $value)
	{
		if(empty($value))
		{
			continue;
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

	$arResult['ELEMENT'] = [];
	$arResult['ENTITY_TYPE'] = [];

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
				$selected[ElementType::getLongEntityType($ar[0])][] = (int)$ar[1];
			}
		}

		DataModifiers\Element::setResultElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setContactElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setCompanyElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setDealElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setOrderElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setQuoteElements($arResult, $arParams, $settings, $selected);
		DataModifiers\Element::setProductElements($arResult, $arParams, $settings, $selected);
	}

	$elements = [];
	foreach($arResult['ELEMENT'] as $element)
	{
		$elements[$element['id']] = $element;
	}
	$arResult['ELEMENT'] = $elements;
}
