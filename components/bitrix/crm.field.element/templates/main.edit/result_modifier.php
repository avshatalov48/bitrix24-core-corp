<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Crm\UserField\DataModifiers;
use Bitrix\Main\Text\HtmlFilter;

if(!Loader::includeModule('crm'))
{
	return;
}

$component = $this->getComponent();

if ($component->isDefaultMode())
{

	CUtil::InitJSCore(['ajax', 'popup']);
	\Bitrix\Main\UI\Extension::load(['sidepanel']);

	$settings = $arParams['userField']['SETTINGS'];
	$supportedTypes = DataModifiers\Element::getSupportedTypes($settings); // all entity
	$arParams['ENTITY_TYPE'] = DataModifiers\Element::getEntityTypes($supportedTypes);  // only entity types are allowed for current user

	$arResult['PERMISSION_DENIED'] = empty($arParams['ENTITY_TYPE']);

	$arResult['PREFIX'] = (count($supportedTypes) > 1 ? 'Y' : 'N');

	if(!empty($arParams['usePrefix']))
	{
		$arResult['PREFIX'] = 'Y';
	}

	$arResult['MULTIPLE'] = $arParams['userField']['MULTIPLE'];

	$arResult['SELECTED_LIST'] = [];

	$selectorEntityTypes = [];

	$arResult['USE_SYMBOLIC_ID'] = (count($supportedTypes) > 1);

	$arResult['LIST_PREFIXES'] = array_flip(ElementType::getEntityTypeNames());

	$arResult['SELECTOR_ENTITY_TYPES'] = ElementType::getSelectorEntityTypes();

	$arResult['DYNAMIC_TYPE_TITLES'] = [];

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

		if (!empty($code))
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
		$arResult['DYNAMIC_TYPE_TITLES'][mb_strtoupper($code)] = \Bitrix\Main\Text\HtmlFilter::encode($type->getTitle());
	}

	$canCreateNewEntity = (
		(!empty($arParams['createNewEntity']) || !empty($arParams['additionalParameters']['createNewEntity']))
		&& LayoutSettings::getCurrent()->isSliderEnabled()
	);
	$arResult['canCreateNewEntity'] = $canCreateNewEntity;

	if ($canCreateNewEntity)
	{
		$arResult['LIST_ENTITY_CREATE_URL'] = [];
		if (!empty($arParams['ENTITY_TYPE']))
		{
			if (count($arParams['ENTITY_TYPE']) > 1)
			{
				$arResult['PLURAL_CREATION'] = true;
			}
			else
			{
				$arResult['PLURAL_CREATION'] = false;
				$arResult['CURRENT_ENTITY_TYPE'] = current($arParams['ENTITY_TYPE']);
			}

			foreach ($arParams['ENTITY_TYPE'] as $entityType)
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
}
else if($component->isMobileMode())
{
	if(is_array($arResult['value']) && count($arResult['value']) > 0)
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

		$arResult['value']['LEAD']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_LEAD');
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
				$arResult['value']['LEAD']['items'][$lead['ID']] = [
					'ENTITY_TITLE' => $lead['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Lead,
						$lead['ID']
					)
				];
			}
		}

		$arResult['value']['CONTACT']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_CONTACT');
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

				$arResult['value']['CONTACT']['items'][$contact['ID']] = [
					'ENTITY_TITLE' => $title,
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Contact,
						$contact['ID']
					)
				];
			}
		}

		$arResult['value']['COMPANY']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_COMPANY');
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
				$arResult['value']['COMPANY']['items'][$company['ID']] = [
					'ENTITY_TITLE' => $company['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Company,
						$company['ID']
					),
				];
			}
		}

		$arResult['value']['DEAL']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_DEAL');
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
				$arResult['value']['DEAL']['items'][$deal['ID']] = [
					'ENTITY_TITLE' => $deal['TITLE'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $deal['ID']),
				];
			}
		}

		$arResult['value']['ORDER']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_ORDER');
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
				$arResult['value']['ORDER']['items'][$order['ID']] = [
					'ENTITY_TITLE' => $order['ACCOUNT_NUMBER'],
					'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
						CCrmOwnerType::Order,
						$order['ID']
					),
				];
			}
		}

		foreach ($arParams['userField']['SETTINGS'] as $entityTypeName => $status)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

			if (!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
			{
				continue;
			}

			if (($factory = Container::getInstance()->getFactory($entityTypeId)) === null)
			{
				continue;
			}

			$arResult['value'][$entityTypeName]['title'] = HtmlFilter::encode($factory->getEntityDescription());

			if ($status === 'Y' && isset($values[$entityTypeName]))
			{
				$list = $factory->getItemsFilteredByPermissions([
					'filter' => ['@ID' => $values[$entityTypeName]],
					'order' => ['ID' => 'DESC'],
				]);
				foreach ($list as $item)
				{
					$itemId = $item->getId();
					$arResult['value'][$entityTypeName]['items'][$itemId] = [
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_TYPE_ID_WITH_ENTITY_ID' => $entityTypeId.'-'.$itemId,
						'ENTITY_TITLE' => HtmlFilter::encode($item->getHeading()),
						'ENTITY_LINK' => Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $itemId),
					];
				}
			}
		}

		$arResult['valueCodes'] = ($arResult['userField']['VALUE'] ?: []);

		if (!is_array($arResult['valueCodes']))
		{
			$arResult['valueCodes'] = [$arResult['valueCodes']];
		}

		$arResult['availableTypes'] = ($arResult['userField']['SETTINGS'] ?? []);

		Asset::getInstance()->addJs(
			'/bitrix/js/mobile/userfield/mobile_field.js'
		);
		Asset::getInstance()->addJs(
			'/bitrix/components/bitrix/crm.field.element/templates/main.view/mobile.js'
		);
	}
}
