<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
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
	public static function getDescription($schemeID)
	{
		if(!is_numeric($schemeID))
		{
			return '';
		}

		$schemeID = (int)$schemeID;
		$descriptions = self::getAllDescriptions();
		return isset($descriptions[$schemeID]) ? $descriptions[$schemeID] : '';
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(!self::$allDescriptions[LANGUAGE_ID])
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$allDescriptions[LANGUAGE_ID] = array(
				self::INVOICE => GetMessage('CRM_DEAL_CONV_INVOICE'),
				self::QUOTE => GetMessage('CRM_DEAL_CONV_QUOTE')
			);
		}
		return self::$allDescriptions[LANGUAGE_ID];
	}
	/**
	* @return array Array of strings
	*/
	public static function getJavaScriptDescriptions($checkPermissions = false)
	{
		$result = array();
		$descriptions = self::getAllDescriptions();

		if(!$checkPermissions)
		{
			$isInvoicePermitted = true;
			$isQuotePermitted = true;
		}
		else
		{
			$flags = array();
			\CCrmDeal::PrepareConversionPermissionFlags(0, $flags);
			$isInvoicePermitted = $flags['CAN_CONVERT_TO_INVOICE'];
			$isQuotePermitted = $flags['CAN_CONVERT_TO_QUOTE'];
		}

		if($isInvoicePermitted && $isQuotePermitted)
		{
			foreach($descriptions as $schemeID => $description)
			{
				$result[self::resolveName($schemeID)] = $description;
			}
		}
		else
		{
			$schemes = array();
			if($isInvoicePermitted)
			{
				$schemes[] = self::INVOICE;
			}
			if($isQuotePermitted)
			{
				$schemes[] = self::QUOTE;
			}

			foreach($schemes as $schemeID)
			{
				$result[self::resolveName($schemeID)] = $descriptions[$schemeID];
			}
		}
		return $result;
	}
}