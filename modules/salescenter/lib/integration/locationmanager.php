<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Service\FormatService;
use Bitrix\Main;

/**
 * Class LocationManager
 * @package Bitrix\SalesCenter\Integration
 */
class LocationManager extends Base
{
	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'location';
	}

	/**
	 * @param int $addressId
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getFormattedLocationArray(int $addressId)
	{
		$address = Address::load((int)$addressId);

		if (!$address
			|| (
				!(float)$address->getLatitude()
				&& !(float)$address->getLongitude()
			)
		)
		{
			return null;
		}

		$location = $address->getLocation();
		if (!$location)
		{
			return null;
		}

		$result = $location->toArray();

		unset($result['id']);

		$result['name'] = $address->toString(
			FormatService::getInstance()->findDefault(LANGUAGE_ID),
			StringConverter::STRATEGY_TYPE_FIELD_TYPE,
			StringConverter::CONTENT_TYPE_TEXT
		);

		$result['address'] = $this->getFormattedAddressArray($addressId);

		return $result;
	}

	/**
	 * @param int $addressId
	 * @return array|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getFormattedAddressArray(int $addressId)
	{
		$address = Address::load((int)$addressId);

		if (!$address)
		{
			return null;
		}

		$result = $address->toArray();

		unset($result['id']);

		return $result;
	}

	/**
	 * @param int $addressId
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function setDefaultLocationFrom(int $addressId)
	{
		$locationArray = $this->getFormattedLocationArray($addressId);
		if (!$locationArray)
		{
			return;
		}

		Main\Config\Option::set(
			'salescenter',
			'default_location_from',
			serialize($locationArray)
		);
	}

	/**
	 * @return mixed|null
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function getDefaultLocationFrom()
	{
		$defaultLocationFrom = Main\Config\Option::get('salescenter', 'default_location_from');

		if (!$defaultLocationFrom)
		{
			return null;
		}

		if (!CheckSerializedData($defaultLocationFrom))
		{
			return null;
		}

		$locationArray = unserialize($defaultLocationFrom);

		if (!$locationArray)
		{
			return null;
		}

		return $locationArray;
	}
}
