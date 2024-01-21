<?php

namespace Bitrix\Market\Integration\Crm;

use Bitrix\Main\Loader;
use Bitrix\Market\Integration\TagHandlerInterface;
use Bitrix\Crm\Service\Container;

/**
 * class TagHandler
 *
 * @package Bitrix\Market\Integration\Crm
 */
class TagHandler implements TagHandlerInterface
{
	private const MODULE_ID = 'crm';
	private const TAG_INVOICE = 'invoice_count';
	private const TAG_LEAD = 'lead_count';
	private const TAG_DEAL = 'deal_count';
	private const TAG_CONTACT = 'contact_count';
	private const TAG_COMPANY = 'company_count';

	private static function getCountInvoiceTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$count = 0;
			$smartInvoice = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
			if ($smartInvoice instanceof \Bitrix\Crm\Service\Factory\SmartInvoice)
			{
				$count = $smartInvoice->getDataClass()::getCount();
			}
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_INVOICE,
				'VALUE' => $count,
			];
		}

		return $result;
	}

	private static function getCountLeadTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_LEAD,
				'VALUE' => \CCrmLead::GetTotalCount(),
			];
		}

		return $result;
	}

	private static function getCountDealTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_DEAL,
				'VALUE' => \CCrmDeal::GetTotalCount(),
			];
		}

		return $result;
	}

	private static function getCountContactTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_CONTACT,
				'VALUE' => \CCrmContact::GetTotalCount(),
			];
		}

		return $result;
	}

	private static function getCountCompanyTag(): array
	{
		$result = [];
		if (Loader::includeModule(static::MODULE_ID))
		{
			$result = [
				'MODULE_ID' => static::MODULE_ID,
				'CODE' => static::TAG_COMPANY,
				'VALUE' => \CCrmCompany::GetTotalCount(),
			];
		}

		return $result;
	}

	/**
	 * Return all Crm service tags.
	 *
	 * @return array
	 */
	public static function list(): array
	{
		return [
			static::getCountInvoiceTag(),
			static::getCountLeadTag(),
			static::getCountDealTag(),
			static::getCountContactTag(),
			static::getCountCompanyTag(),
		];
	}
}