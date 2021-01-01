<?php

namespace Bitrix\Location\Infrastructure;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Source\Factory;
use Bitrix\Location\Model\AddressTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;

/**
 * Class AddressLimit
 * @package Bitrix\Location\Entity\Address
 * @internal
 */
final class AddressLimit
{
	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isLimitReached(): bool
	{
		static $result = null;

		if($result !== null)
		{
			return $result;
		}

		$result = false;

		if(!Loader::includeModule('bitrix24'))
		{
			return $result;
		}

		$value = static::getLimitValue();

		if($value > 0)
		{
			$res = AddressTable::getList([
				'select' => ['CNT'],
				'filter' => [
					'=LOCATION.SOURCE_CODE' => 'GOOGLE'
				],
				'runtime' => [
					new ExpressionField('CNT', 'COUNT(*)')
				],
				'limit' => $value
			]);

			if($row = $res->fetch())
			{
				$result = (int)$row['CNT'] >= $value;
			}
		}

		return $result;
	}

	public static function isAddressForLimitation(Address $address): bool
	{
		$result = false;

		if($location = $address->getLocation())
		{
			if(static::isSourceLimited($location->getSourceCode()))
			{
				$result = true;
			}
		}

		return  $result;
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function isSourceLimited(string $code): bool
	{
		return $code === Factory::GOOGLE_SOURCE_CODE;
	}

	private static function getLimitValue(): int
	{
		return (int)\Bitrix\Bitrix24\Feature::getVariable('location_google_address_limit');
	}
}