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

$component = $this->getComponent();

if ($component->isDefaultMode())
{

	CUtil::InitJSCore(['ajax', 'popup']);
	\Bitrix\Main\UI\Extension::load(['sidepanel']);

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();

	$settings = $arParams['userField']['SETTINGS'];
	$supportedTypes = DataModifiers\Element::getSupportedTypes($settings); // all entity
	$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes, $userPermissions);  // only entity types are allowed for current user

	$arResult['PERMISSION_DENIED'] = (empty($arParams['ENTITY_TYPE']) ? true : false);

	$arResult['PREFIX'] = (count($supportedTypes) > 1 ? 'Y' : 'N');

	if (!empty($arParams['usePrefix']))
	{
		$arResult['PREFIX'] = 'Y';
	}

	$arResult['MULTIPLE'] = $arParams['userField']['MULTIPLE'];

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

	if (!is_array($arResult['value']))
	{
		$arResult['value'] = explode(';', $arResult['value']);
	}
	else
	{
		$values = [];
		foreach ($arResult['value'] as $value)
		{
			foreach (explode(';', $value) as $val)
			{
				if (!empty($val))
				{
					$values[$val] = $val;
				}
			}
		}
		$arResult['value'] = $values;
	}

	foreach ($arResult['value'] as $key => $value)
	{
		if (empty($value))
		{
			continue;
		}

		if ($arResult['USE_SYMBOLIC_ID'])
		{
			$code = '';
			foreach ($arResult['LIST_PREFIXES'] as $type => $prefix)
			{
				if (preg_match('/^' . $prefix . '_(\d+)$/i', $value, $matches))
				{
					$code = $arResult['SELECTOR_ENTITY_TYPES'][$type];
					break;
				}
			}
		}
		elseif (preg_match('/(\d+)$/i', $value, $matches))
		{
			foreach ($arParams['ENTITY_TYPE'] as $entityType)
			{
				if (!empty($entityType))
				{
					$value = $arResult['LIST_PREFIXES'][$entityType] . '_' . $matches[1];
					$code = $arResult['SELECTOR_ENTITY_TYPES'][$entityType];
					break;
				}
			}
		}

		if (!empty($code))
		{
			$arResult['SELECTED_LIST'][$value] = $code;
		}
	}

	$arParams['createNewEntity'] = (
		$arParams['createNewEntity']
		&&
		LayoutSettings::getCurrent()->isSliderEnabled()
	);

	if (!empty($arParams['createNewEntity']))
	{
		if (!empty($arResult['ENTITY_TYPE']))
		{
			if (count($arResult['ENTITY_TYPE']) > 1)
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

		foreach ($arResult['ENTITY_TYPE'] as $entityType)
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
}
else if($component->isMobileMode())
{

	if(is_array($arResult['value']) && count($arResult['value']) > 0)
	{
		$arParams['ENTITY_TYPE'] = DataModifiers\Element::getSupportedTypes(
			$arParams['userField']['SETTINGS']
		);

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

		if(
			$arParams['userField']['SETTINGS']['LEAD'] === 'Y'
			&&
			!empty($values['LEAD'])
		)
		{
			$leads = CCrmLead::GetListEx(
				['TITLE' => 'ASC'],
				['=ID' => $values['LEAD']],
				false,
				false,
				['ID', 'TITLE']
			);
			while($lead = $leads->Fetch())
			{
				$arResult['value']['LEAD'][$lead['ID']] = [
					'ENTITY_TITLE' => $lead['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Lead,
						$lead['ID']
					)
				];
			}
		}

		if(
			$arParams['userField']['SETTINGS']['CONTACT'] === 'Y'
			&&
			!empty($values['CONTACT'])
		)
		{
			$hasNameFormatter = method_exists('CCrmContact', 'PrepareFormattedName');
			$contatcs = CCrmContact::GetListEx(
				['LAST_NAME' => 'ASC', 'NAME' => 'ASC'],
				['=ID' => $values['CONTACT']],
				false,
				false,
				$hasNameFormatter
					? ['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
					: ['ID', 'FULL_NAME']
			);

			while($contact = $contatcs->Fetch())
			{
				if($hasNameFormatter)
				{
					$title = CCrmContact::PrepareFormattedName(
						[
							'HONORIFIC' => ($contact['HONORIFIC'] ?? ''),
							'NAME' => ($contact['NAME'] ?? ''),
							'SECOND_NAME' => ($contact['SECOND_NAME'] ?? ''),
							'LAST_NAME' => ($contact['LAST_NAME'] ?? '')
						]
					);
				}
				else
				{
					$title = ($contact['FULL_NAME'] ?? '');
				}

				$arResult['value']['CONTACT'][$contact['ID']] = [
					'ENTITY_TITLE' => $title,
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Contact,
						$contact['ID']
					)
				];
			}
		}

		if(
			$arParams['userField']['SETTINGS']['COMPANY'] === 'Y'
			&&
			!empty($values['COMPANY'])
		)
		{
			$companies = CCrmCompany::GetListEx(
				['TITLE' => 'ASC'],
				['ID' => $values['COMPANY']]
			);
			while($company = $companies->Fetch())
			{
				$arResult['value']['COMPANY'][$company['ID']] = [
					'ENTITY_TITLE' => $company['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Company,
						$company['ID']
					),
				];
			}
		}

		if(
			$arParams['userField']['SETTINGS']['DEAL'] === 'Y'
			&&
			!empty($values['DEAL'])
		)
		{
			$deals = CCrmDeal::GetListEx(
				['TITLE' => 'ASC'],
				['ID' => $values['DEAL']]
			);
			while($deal = $deals->Fetch())
			{
				$arResult['value']['DEAL'][$deal['ID']] = [
					'ENTITY_TITLE' => $deal['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $deal['ID']),
				];
			}
		}

		if(
			$arParams['userField']['SETTINGS']['ORDER'] === 'Y'
			&&
			!empty($values['ORDER'])
		)
		{
			$orders = \Bitrix\Crm\Order\Order::getList([
				'filter' => ['=ID' => $values['ORDER']],
				'select' => ['ID', 'ACCOUNT_NUMBER'],
				'order' => ['ID' => 'DESC']
			]);

			while($order = $orders->fetch())
			{
				$arResult['value']['ORDER'][$order['ID']] = [
					'ENTITY_TITLE' => $order['ACCOUNT_NUMBER'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Order,
						$order['ID']
					),
				];
			}
		}

		$arResult['ELEMENT'] = $arResult['value'];
		$arResult['value'] = ($arResult['userField']['VALUE'] ?: []);

		if (!is_array($arResult['value']))
		{
			$arResult['value'] = [$arResult['value']];
		}

		Asset::getInstance()->addJs(
			'/bitrix/js/mobile/userfield/mobile_field.js'
		);
		Asset::getInstance()->addJs(
			'/bitrix/components/bitrix/crm.field.element/templates/main.view/mobile.js'
		);
	}
}