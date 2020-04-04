<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2017 Bitrix
 */
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

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

	public static function OnUISelectorGetProviderByEntityType(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'crm');

		$entityType = $event->getParameter('entityType');

		switch($entityType)
		{
			case self::ENTITY_TYPE_CRMCONTACTS:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmContacts;
				break;
			case self::ENTITY_TYPE_CRMCOMPANIES:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmCompanies;
				break;
			case self::ENTITY_TYPE_CRMLEADS:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmLeads;
				break;
			case self::ENTITY_TYPE_CRMDEALS:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmDeals;
				break;
			case self::ENTITY_TYPE_CRMORDERS:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmOrders;
				break;
			case self::ENTITY_TYPE_CRMPRODUCTS:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmProducts;
				break;
			case self::ENTITY_TYPE_CRMQUOTES:
				$provider = new \Bitrix\Crm\Integration\Main\UISelector\CrmQuotes;
				break;
			default:
				$provider = false;
		}

		if ($provider)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				array(
					'result' => $provider
				),
				'crm'
			);
		}

		return $result;
	}

	public static function OnUISelectorBeforeSave(Event $event)
	{
		$result = new EventResult(EventResult::UNDEFINED, null, 'crm');
		$code = $event->getParameter('code');

		if (preg_match('/^'.CrmLeads::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmLeads::PREFIX_SHORT.'(\d+)$/', CrmLeads::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmCompanies::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmCompanies::PREFIX_SHORT.'(\d+)$/', CrmCompanies::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmContacts::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmContacts::PREFIX_SHORT.'(\d+)$/', CrmContacts::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmDeals::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmDeals::PREFIX_SHORT.'(\d+)$/', CrmDeals::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmOrders::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmOrders::PREFIX_SHORT.'(\d+)$/', CrmOrders::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmProducts::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmProducts::PREFIX_SHORT.'(\d+)$/', CrmProducts::PREFIX_FULL.'$1', $code);
		}
		elseif (preg_match('/^'.CrmQuotes::PREFIX_SHORT.'(\d+)$/i', $code, $matches))
		{
			$newCode = preg_replace('/^'.CrmQuotes::PREFIX_SHORT.'(\d+)$/', CrmQuotes::PREFIX_FULL.'$1', $code);
		}
		else
		{
			$newCode = false;
		}

		if ($newCode)
		{
			$result = new EventResult(
				EventResult::SUCCESS,
				[
					'code' => $newCode
				],
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

		$crmContactCounter = $crmCompanyCounter = $crmDealCounter = $crmLeadCounter = $crmOrderCounter = $crmProductCounter = $crmQuoteCounter = 0;

		if (is_array($destSortData))
		{
		$crmContactLimit = $crmCompanyLimit = $crmDealLimit = $crmLeadLimit = $crmOrderLimit = $crmProductLimit = $crmQuoteLimit = 10;

			foreach($destSortData as $code => $sortInfo)
			{
				if(
					$crmContactCounter >= $crmContactLimit
					&& $crmCompanyCounter >= $crmCompanyLimit
					&& $crmDealCounter >= $crmDealLimit
					&& $crmLeadCounter >= $crmLeadLimit
					&& $crmOrderCounter >= $crmOrderLimit
					&& $crmProductCounter >= $crmProductLimit
					&& $crmQuoteCounter >= $crmQuoteLimit
				)
				{
					break;
				}

				if(preg_match('/^CRMCONTACT(\d+)$/i', $code, $matches))
				{
					if($crmContactCounter >= $crmContactLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['CONTACTS']))
					{
						$lastDestinationList['CONTACTS'] = [];
					}
					$lastDestinationList['CONTACTS'][$code] = $code;
					$crmContactCounter++;
				}
				elseif(preg_match('/^CRMCOMPANY(\d+)$/i', $code, $matches))
				{
					if($crmCompanyCounter >= $crmCompanyLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['COMPANIES']))
					{
						$lastDestinationList['COMPANIES'] = [];
					}
					$lastDestinationList['COMPANIES'][$code] = $code;
					$crmCompanyCounter++;
				}
				elseif(preg_match('/^CRMDEAL(\d+)$/i', $code, $matches))
				{
					if($crmDealCounter >= $crmDealLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['DEALS']))
					{
						$lastDestinationList['DEALS'] = [];
					}
					$lastDestinationList['DEALS'][$code] = $code;
					$crmDealCounter++;
				}
				elseif(preg_match('/^CRMLEAD(\d+)$/i', $code, $matches))
				{
					if($crmLeadCounter >= $crmLeadLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['LEADS']))
					{
						$lastDestinationList['LEADS'] = [];
					}
					$lastDestinationList['LEADS'][$code] = $code;
					$crmLeadCounter++;
				}
				elseif(preg_match('/^CRMORDER(\d+)$/i', $code, $matches))
				{
					if($crmOrderCounter >= $crmOrderLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['ORDERS']))
					{
						$lastDestinationList['ORDERS'] = [];
					}
					$lastDestinationList['ORDERS'][$code] = $code;
					$crmOrderCounter++;
				}
				elseif(preg_match('/^CRMPRODUCT(\d+)$/i', $code, $matches))
				{
					if($crmProductCounter >= $crmProductLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['PRODUCTS']))
					{
						$lastDestinationList['PRODUCTS'] = [];
					}
					$lastDestinationList['PRODUCTS'][$code] = $code;
					$crmProductCounter++;
				}
				elseif(preg_match('/^CRMQUOTE(\d+)$/i', $code, $matches))
				{
					if($crmQuoteCounter >= $crmQuoteLimit)
					{
						continue;
					}
					if(!isset($lastDestinationList['QUOTES']))
					{
						$lastDestinationList['QUOTES'] = [];
					}
					$lastDestinationList['QUOTES'][$code] = $code;
					$crmQuoteCounter++;
				}
			}

			$result = new EventResult(
				EventResult::SUCCESS,
				[
					'lastDestinationList' => $lastDestinationList
				],
				'crm'
			);
		}

		return $result;
	}
}
