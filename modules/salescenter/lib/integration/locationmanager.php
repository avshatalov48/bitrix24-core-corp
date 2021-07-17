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
	 */
	private function getFormattedLocation(int $addressId)
	{
		$address = Address::load($addressId);

		if (!$address)
		{
			return null;
		}

		$result = [];
		$location = $address->getLocation();
		if ($location)
		{
			$result = $location->toArray();
			unset($result['id']);
		}

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
	 */
	public function getFormattedAddressArray(int $addressId)
	{
		$address = Address::load($addressId);

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
	 */
	public function setDefaultLocationFrom(int $addressId)
	{
		$locationArray = $this->getFormattedLocation($addressId);
		//$locationArray = $this->getFormattedAddressArray($addressId);
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

		$locationArray = unserialize($defaultLocationFrom, ['allowed_classes' => false]);

		if (!$locationArray)
		{
			return null;
		}

		return $locationArray;
	}

	/**
	 * @param int[] $ids
	 * @return array
	 */
	public function getFormattedLocations(array $ids): array
	{
		$result = [];

		foreach ($ids as $id)
		{
			$formattedAddress = $this->getFormattedLocation($id);
			if (!$formattedAddress)
			{
				continue;
			}

			$result[] = $formattedAddress;
		}

		return $result;
	}
}
