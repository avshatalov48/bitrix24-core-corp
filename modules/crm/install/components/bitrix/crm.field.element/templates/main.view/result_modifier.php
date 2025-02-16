<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
/** @var array $arParams */

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\DataModifiers;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Text\HtmlFilter;

if(!Loader::includeModule('crm'))
{
	return;
}

$emptyEntityLabels = [];

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
			$values[reset($arParams['ENTITY_TYPE'])][] = $value;
		}
		elseif (is_string($value))
		{
			$ar = explode('_', $value);
			if (count($ar) > 1)
			{
				$values[ElementType::getLongEntityType($ar[0])][] = (int)$ar[1];
			}
		}
	}

	$arResult['value'] = [];

	$arResult['value']['LEAD']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_LEAD');
	if(
		$arParams['userField']['SETTINGS']['LEAD'] === 'Y'
		&& !empty($values['LEAD'])
	)
	{
		$leads = CCrmLead::GetListEx(
			['TITLE' => 'ASC'],
			['=ID' => $values['LEAD']],
			false,
			false,
			['ID', 'TITLE']
		);
		$arResult['value']['LEAD']['tooltipLoaderUrl'] = '/bitrix/components/bitrix/crm.lead.show/card.ajax.php';

		while($lead = $leads->Fetch())
		{
			$arResult['value']['LEAD']['items'][$lead['ID']] = [
				'ENTITY_TITLE' => $lead['TITLE'],
				'ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'LEAD_'.$lead['ID'],
				'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'L_' . $lead['ID'],
				'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
					CCrmOwnerType::Lead,
					$lead['ID']
				)
			];
		}

		if (empty($arResult['value']['LEAD']['items']))
		{
			$emptyEntityLabels['LEAD'] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
		}
	}

	$arResult['value']['CONTACT']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_CONTACT');
	if(
		($arParams['userField']['SETTINGS']['CONTACT'] ?? null) === 'Y'
		&& !empty($values['CONTACT'])
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
		$arResult['value']['CONTACT']['tooltipLoaderUrl'] = '/bitrix/components/bitrix/crm.contact.show/card.ajax.php';

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
				'ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'CONTACT_'.$contact['ID'],
				'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'C_' . $contact['ID'],
				'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
					CCrmOwnerType::Contact,
					$contact['ID']
				)
			];
		}

		if (empty($arResult['value']['CONTACT']['items']))
		{
			$emptyEntityLabels['CONTACT'] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
		}
	}

	$arResult['value']['COMPANY']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_COMPANY');
	if(
		($arParams['userField']['SETTINGS']['COMPANY'] ?? null) === 'Y'
		&& !empty($values['COMPANY'])
	)
	{
		$companies = CCrmCompany::GetListEx(
			['TITLE' => 'ASC'],
			['ID' => $values['COMPANY']],
			false,
			false,
			[
				'ID',
				'TITLE',
			]
		);
		$arResult['value']['COMPANY']['tooltipLoaderUrl'] = '/bitrix/components/bitrix/crm.company.show/card.ajax.php';

		while($company = $companies->Fetch())
		{
			$companyId = $company['ID'];
			$arResult['value']['COMPANY']['items'][$companyId] = [
				'ENTITY_TITLE' => $company['TITLE'],
				'ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'COMPANY_' . $companyId,
				'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'CO_' . $companyId,
				'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Company, $companyId),
			];
		}

		if (empty($arResult['value']['COMPANY']['items']))
		{
			$emptyEntityLabels['COMPANY'] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
		}
	}

	$arResult['value']['DEAL']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_DEAL');
	if (
		isset($arParams['userField']['SETTINGS']['DEAL'])
		&& $arParams['userField']['SETTINGS']['DEAL'] === 'Y'
		&& !empty($values['DEAL'])
	)
	{
		$deals = CCrmDeal::GetListEx(
			['TITLE' => 'ASC'],
			['ID' => $values['DEAL']],
			false,
			false,
			[
				'ID',
				'TITLE',
			]
		);
		$arResult['value']['DEAL']['tooltipLoaderUrl'] = '/bitrix/components/bitrix/crm.deal.show/card.ajax.php';

		while ($deal = $deals->Fetch())
		{
			$dealId = $deal['ID'];
			$arResult['value']['DEAL']['items'][$dealId] = [
				'ENTITY_TITLE' => $deal['TITLE'],
				'ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'DEAL_' . $dealId,
				'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'D_' . $dealId,
				'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $dealId),
			];
		}

		if (empty($arResult['value']['DEAL']['items']))
		{
			$emptyEntityLabels['DEAL'] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
		}
	}

	$arResult['value']['ORDER']['title'] = Loc::getMessage('CRM_ENTITY_TYPE_ORDER');
	if (
		isset($arParams['userField']['SETTINGS']['ORDER'])
		&& $arParams['userField']['SETTINGS']['ORDER'] === 'Y'
		&& !empty($values['ORDER'])
	)
	{
		$orders = \Bitrix\Crm\Order\Order::getList([
			'filter' => ['=ID' => $values['ORDER']],
			'select' => ['ID', 'ACCOUNT_NUMBER'],
			'order' => ['ID' => 'DESC']
		]);

		$arResult['value']['ORDER']['tooltipLoaderUrl'] = '/bitrix/components/bitrix/crm.order.details/card.ajax.php';

		while($order = $orders->fetch())
		{
			$arResult['value']['ORDER']['items'][$order['ID']] = [
				'ENTITY_TITLE' => $order['ACCOUNT_NUMBER'],
				'ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'ORDER_'.$order['ID'],
				'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => 'O_' . $order['ID'],
				'ENTITY_LINK' => CCrmOwnerType::GetEntityShowPath(
					CCrmOwnerType::Order,
					$order['ID']
				),
			];
		}

		if (empty($arResult['value']['ORDER']['items']))
		{
			$emptyEntityLabels['ORDER'] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
		}
	}

	$uri = UrlManager::getInstance()->create(
		'bitrix:crm.controller.tooltip.card',
		[
			'sessid' => bitrix_sessid(),
		]
	);
	foreach ($arParams['userField']['SETTINGS'] as $entityTypeName => $status)
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		if (!\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			continue;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			continue;
		}
		if (isset($arResult['value'][$entityTypeName]))
		{
			continue;
		}

		$arResult['value'][$entityTypeName]['title'] = HtmlFilter::encode($factory->getEntityDescription());

		if ($status === 'Y' && isset($values[$entityTypeName]))
		{
			$arResult['value'][$entityTypeName]['tooltipLoaderUrl'] = $uri;
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
					'SHORT_ENTITY_TYPE_ID_WITH_ENTITY_ID' => \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId) . '_' . $itemId,
					'ENTITY_TITLE' => $item->getHeading(),
					'ENTITY_LINK' => Container::getInstance()->getRouter()->getItemDetailUrl($entityTypeId, $itemId),
				];
			}

			if (empty($arResult['value'][$entityTypeName]['items']))
			{
				$emptyEntityLabels[$entityTypeName] = Loc::getMessage('CRM_ELEMENT_HIDDEN_OR_DELETED');
			}
		}
	}

	/**
	 * @var $component ElementCrmUfComponent
	 */

	$component = $this->getComponent();

	if($component->isMobileMode())
	{
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

	$arResult['emptyEntityLabels'] = $emptyEntityLabels;
}
