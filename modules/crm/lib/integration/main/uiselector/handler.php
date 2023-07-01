<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2017 Bitrix
 */

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\UserField\Types\ElementType;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;

Loc::loadMessages(__FILE__);

class Handler
{
	const ENTITY_TYPE_CRMCONTACTS = 'CONTACTS';
	const ENTITY_TYPE_CRMCOMPANIES = 'COMPANIES';
	const ENTITY_TYPE_CRMLEADS = 'LEADS';
	const ENTITY_TYPE_CRMDEALS = 'DEALS';
	const ENTITY_TYPE_CRMORDERS = 'ORDERS';
	const ENTITY_TYPE_CRMPRODUCTS = 'PRODUCTS';
	const ENTITY_TYPE_CRMQUOTES = 'QUOTES';
	const ENTITY_TYPE_CRMSMART_INVOICES = 'SMART_INVOICES';
	const ENTITY_TYPE_CRMDYNAMICS = 'DYNAMICS';

	public static function getProviderByEntityType(string $entityType)
	{
		$provider = null;

		switch($entityType)
		{
			case self::ENTITY_TYPE_CRMCONTACTS:
				$provider = new CrmContacts;
				break;
			case self::ENTITY_TYPE_CRMCOMPANIES:
				$provider = new CrmCompanies;
				break;
			case self::ENTITY_TYPE_CRMLEADS:
				$provider = new CrmLeads;
				break;
			case self::ENTITY_TYPE_CRMDEALS:
				$provider = new CrmDeals;
				break;
			case self::ENTITY_TYPE_CRMORDERS:
				$provider = new CrmOrders;
				break;
			case self::ENTITY_TYPE_CRMPRODUCTS:
				$provider = new CrmProducts;
				break;
			case self::ENTITY_TYPE_CRMQUOTES:
				$provider = new CrmQuotes;
				break;
			case self::ENTITY_TYPE_CRMSMART_INVOICES:
				$provider = new CrmSmartInvoices();
				break;
			case (mb_strpos($entityType, self::ENTITY_TYPE_CRMDYNAMICS) === 0):
				$provider = new CrmDynamics;
				break;
		}

		return $provider;
	}

	public static function OnUISelectorGetProviderByEntityType(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'crm');

		$entityType = $event->getParameter('entityType');

		$provider = static::getProviderByEntityType($entityType);

		if ($provider)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				['result' => $provider],
				'crm'
			);
		}

		return $result;
	}

	public static function OnUISelectorBeforeSave(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'crm');
		$code = $event->getParameter('code');

		$providerClassList = [
			'CrmLeads',
			'CrmCompanies',
			'CrmContacts',
			'CrmDeals',
			'CrmQuotes',
			'CrmOrders',
			'CrmProducts',
			'CrmQuotes',
			'CrmSmartInvoices',
		];

		$newCode = '';
		$isNewCodeSet = false;
		foreach ($providerClassList as $className)
		{
			$className = __NAMESPACE__ . '\\' . $className;
			if (preg_match('/^' . $className::PREFIX_SHORT . '(\d+)$/i', $code, $matches))
			{
				$newCode = preg_replace(
					'/^' . $className::PREFIX_SHORT . '(\d+)$/',
					$className::PREFIX_FULL . '$1',
					$code
				);
				$isNewCodeSet = true;
				break;
			}
		}

		if (
			!$isNewCodeSet
			&& preg_match(
				'/^'. CCrmOwnerTypeAbbr::DynamicTypeAbbreviationPrefix . '(\w+)_(\d+)$/i',
				$code,
				$matches
			)
		)
		{
			$newCode = preg_replace(
				'/^' . CCrmOwnerTypeAbbr::DynamicTypeAbbreviationPrefix . '(\w+)_(\d+)$/',
				CrmDynamics::PREFIX_FULL . '$1' . '_' . '$2',
				$code
			);
			$isNewCodeSet = true;
		}

		if ($isNewCodeSet)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				['code' => $newCode],
				'crm'
			);
		}

		return $result;
	}

	public static function OnUISelectorFillLastDestination(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'crm');

		$params = $event->getParameter('params');
		$destSortData = $event->getParameter('destSortData');

		if (
			!is_array($params)
			|| !isset($params["CRM"])
			|| $params["CRM"] != "Y"
		)
		{
			return $result;
		}

		$lastDestinationList = [];

		$limit = 10;
		$conditionList = [
			[
				'type' => CCrmOwnerType::ContactName,
				'index' => static::ENTITY_TYPE_CRMCONTACTS,
				'counter' => 0,
				'limit' => $limit,
				'multi' => true,
			],
			[
				'type' => CCrmOwnerType::CompanyName,
				'index' => static::ENTITY_TYPE_CRMCOMPANIES,
				'counter' => 0,
				'limit' => $limit,
				'multi' => true,
			],
			[
				'type' => CCrmOwnerType::DealName,
				'index' => static::ENTITY_TYPE_CRMDEALS,
				'counter' => 0,
				'limit' => $limit,
				'multi' => false,
			],
			[
				'type' => CCrmOwnerType::QuoteName,
				'index' => static::ENTITY_TYPE_CRMQUOTES,
				'counter' => 0,
				'limit' => $limit,
				'multi' => false,
			],
			[
				'type' => CCrmOwnerType::LeadName,
				'index' => static::ENTITY_TYPE_CRMLEADS,
				'counter' => 0,
				'limit' => $limit,
				'multi' => true,
			],
			[
				'type' => CCrmOwnerType::OrderName,
				'index' => static::ENTITY_TYPE_CRMORDERS,
				'counter' => 0,
				'limit' => $limit,
				'multi' => false,
			],
			[
				'type' => 'PRODUCT',
				'index' => static::ENTITY_TYPE_CRMPRODUCTS,
				'counter' => 0,
				'limit' => $limit,
				'multi' => false,
			],
		];
		
		$selectorEntityTypes = ElementType::getSelectorEntityTypes();

		if (is_array($destSortData))
		{
			foreach(array_keys($destSortData) as $code)
			{
				$doBreak = true;
				foreach ($conditionList as $condition)
				{
					if ($condition['counter'] < $condition['limit'])
					{
						$doBreak = false;
						break;
					}
				}
				if ($doBreak)
				{
					break;
				}
				unset($doBreak);

				foreach ($conditionList as $condition)
				{
					$matches = [];
					if (preg_match('/^CRM' . $condition['type'] . '(\d+)$/i', $code, $matches))
					{
						if ($condition['counter'] >= $condition['limit'])
						{
							break;
						}
						if (!isset($lastDestinationList[$condition['index']]))
						{
							$lastDestinationList[$condition['index']] = [];
						}
						$lastDestinationList[$condition['index']][$code] = $code;
						$condition['counter']++;
						break;
					}
					elseif (
						$condition['multi']
						&& isset($params['MULTI'])
						&& $params['MULTI'] === 'Y'
						&& preg_match('/^CRM' . $condition['type'] . '(\d+)(:([A-F0-9]{8}))?$/i', $code, $matches)
					)
					{
						if ($condition['counter'] >= $condition['limit'])
						{
							break;
						}
						$index = $condition['index'] . '_MULTI';
						if (!isset($lastDestinationList[$index]))
						{
							$lastDestinationList[$index] = [];
						}
						$lastDestinationList[$index][$code] = $code;
						$condition['counter']++;
						break;
					}
				}
			}

			$result = new EventResult(
				EventResult::SUCCESS,
				['lastDestinationList' => $lastDestinationList],
				'crm'
			);
		}

		return $result;
	}
}
