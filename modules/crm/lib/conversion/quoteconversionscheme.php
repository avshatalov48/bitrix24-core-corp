<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Settings\InvoiceSettings;

/**
 * @deprecated
 */
class QuoteConversionScheme
{
	const UNDEFINED = 0;
	const DEAL = 1;
	const INVOICE = 2;

	const DEAL_NAME = 'DEAL';
	const INVOICE_NAME = 'INVOICE';

	private static $allDescriptions = array();

	public static function isDefined($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return false;
		}

		$schemeID = (int)$schemeID;
		return $schemeID >= self::DEAL && $schemeID <= self::INVOICE;
	}
	public static function getDefault()
	{
		return self::DEAL;
	}
	public static function resolveName($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return '';
		}

		$schemeID = (int)$schemeID;
		if($schemeID <= 0)
		{
			return '';
		}

		switch($schemeID)
		{
			case self::DEAL:
				return self::DEAL_NAME;
			case self::INVOICE:
				return self::INVOICE_NAME;
			case self::UNDEFINED:
			default:
				return '';
		}
	}

	/**
	 * Returns all schemes descriptions disregarding settings
	 *
	 * @return array
	 */
	private static function getAllDescriptionsInner(): array
	{
		if(!isset(self::$allDescriptions[LANGUAGE_ID]))
		{
			self::$allDescriptions[LANGUAGE_ID] = [
				self::DEAL => GetMessage('CRM_QUOTE_CONV_DEAL'),
				self::INVOICE => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Invoice),
			];
		}

		return self::$allDescriptions[LANGUAGE_ID];
	}

	public static function getDescription($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return '';
		}

		$schemeID = (int)$schemeID;
		$descriptions = self::getAllDescriptionsInner();
		return isset($descriptions[$schemeID]) ? $descriptions[$schemeID] : '';
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		$descriptions = static::getAllDescriptionsInner();
		if (!InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
		{
			unset($descriptions[self::INVOICE]);
		}

		return $descriptions;
	}
	/**
	* @return array Array of strings
	*/
	public static function getJavaScriptDescriptions($checkPermissions = false)
	{
		$permissions = [
			self::DEAL => true,
			self::INVOICE => true,
		];
		if ($checkPermissions)
		{
			$flags = [];
			\CCrmQuote::PrepareConversionPermissionFlags(0, $flags);
			$permissions[self::DEAL] = $flags['CAN_CONVERT_TO_DEAL'];
			$permissions[self::INVOICE] = $flags['CAN_CONVERT_TO_INVOICE'];
		}

		$result = [];
		foreach (self::getAllDescriptionsInner() as $schemeId => $description)
		{
			$isPermitted = $permissions[$schemeId] ?? true;
			if ($isPermitted)
			{
				$result[self::resolveName($schemeId)] = $description;
			}
		}

		return $result;
	}

	public static function getEntityTypeIds(int $schemeId): array
	{
		if ($schemeId === static::INVOICE)
		{
			return [\CCrmOwnerType::Invoice];
		}
		if ($schemeId === static::DEAL)
		{
			return [\CCrmOwnerType::Deal];
		}

		return [];
	}
}
