<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Crm\Settings\InvoiceSettings;

class DealConversionScheme
{
	const UNDEFINED = 0;
	const INVOICE = 1;
	const QUOTE = 2;

	const INVOICE_NAME = 'INVOICE';
	const QUOTE_NAME = 'QUOTE';

	private static $allDescriptions = array();

	public static function isDefined($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return false;
		}

		$schemeID = (int)$schemeID;
		return $schemeID >= self::INVOICE && $schemeID <= self::QUOTE;
	}
	public static function getDefault()
	{
		return self::INVOICE;
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
			case self::INVOICE:
				return self::INVOICE_NAME;
			case self::QUOTE:
				return self::QUOTE_NAME;
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
				self::QUOTE => GetMessage('CRM_DEAL_CONV_QUOTE_MSGVER_1'),
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
			self::INVOICE => true,
			self::QUOTE => true,
		];
		if ($checkPermissions)
		{
			$flags = [];
			\CCrmDeal::PrepareConversionPermissionFlags(0, $flags);
			$permissions[self::INVOICE] = $flags['CAN_CONVERT_TO_INVOICE'];
			$permissions[self::QUOTE] = $flags['CAN_CONVERT_TO_QUOTE'];
		}

		$result = [];
		foreach (static::getAllDescriptionsInner() as $schemeId => $description)
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
		if ($schemeId === static::QUOTE)
		{
			return [\CCrmOwnerType::Quote];
		}

		return [];
	}
}
