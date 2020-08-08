<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;
use Bitrix\Crm\UserField\DataModifiers;

if(!Loader::includeModule('crm'))
{
	return;
}

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


	/**
	 * @var $component ElementCrmUfComponent
	 */

	$component = $this->getComponent();

	if($component->isMobileMode())
	{
		$arResult['ELEMENT'] = $arResult['value'];

		$arResult['value'] = ($arResult['userField']['VALUE'] ?: []);

		Asset::getInstance()->addJs(
			'/bitrix/js/mobile/userfield/mobile_field.js'
		);
		Asset::getInstance()->addJs(
			'/bitrix/components/bitrix/crm.field.element/templates/main.view/mobile.js'
		);
	}
}