<?php
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class MessageHelper
{
	/**
	 * @deprecated
	 */
	public static function getNumberDeclension($number, $nominative, $genitiveSingular, $genitivePlural)
	{
		$number = intval($number);
		if($number === 0)
		{
			return $genitivePlural;
		}

		if($number < 0)
		{
			$number = -$number;
		}

		$lastDigit = $number % 10;
		$penultimateDigit = (($number % 100) - $lastDigit) / 10;

		if ($lastDigit === 1 && $penultimateDigit !== 1)
		{
			return $nominative;
		}

		return ($penultimateDigit !== 1 && $lastDigit >= 2 && $lastDigit <= 4)
			? $genitiveSingular : $genitivePlural;
	}

	/**
	 * @deprecated
	 */
	public static function getEntityNumberDeclensionMessages($entityTypeID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		IncludeModuleLangFile(__FILE__);

		return array(
			'nominative' => Loc::getMessage("CRM_{$entityTypeName}_COUNT_0"),
			'genitiveSingular' => Loc::getMessage("CRM_{$entityTypeName}_COUNT_1"),
			'genitivePlural' => Loc::getMessage("CRM_{$entityTypeName}_COUNT_2")
		);
	}

	public static function getEntityPluralMessages($entityTypeID)
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		IncludeModuleLangFile(__FILE__);

		return [
			"CRM_{$entityTypeName}_PLURAL_0" => Loc::getMessage("CRM_{$entityTypeName}_PLURAL_0"),
			"CRM_{$entityTypeName}_PLURAL_1" => Loc::getMessage("CRM_{$entityTypeName}_PLURAL_1"),
			"CRM_{$entityTypeName}_PLURAL_2" => Loc::getMessage("CRM_{$entityTypeName}_PLURAL_2"),
		];
	}

	/**
	 * @deprecated
	 */
	public static function prepareEntityNumberDeclension($entityTypeID, $number)
	{
		$number = (int)$number;
		if($number === 0)
		{
			return '';
		}

		if($number < 0)
		{
			$number = -$number;
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);

		IncludeModuleLangFile(__FILE__);
		return Loc::getMessagePlural("CRM_{$entityTypeName}", $number);
	}
}