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
	public function storeLocationFrom(int $addressId): void
	{
		$location = $this->getFormattedLocation($addressId);
		if (!$location)
		{
			return;
		}

		$newLocations = [
			$this->makeLocationCode($location) => $location,
		];
		$currentLocations = $this->getLocationsFromList();
		if (is_array($currentLocations))
		{
			$newLocations = array_merge($newLocations, $currentLocations);
		}

		Main\Config\Option::set(
			'salescenter',
			'default_location_from',
			serialize($newLocations)
		);
	}

	/**
	 * @return mixed|null
	 */
	public function getLocationsFromList(): ?array
	{
		$optionValue = Main\Config\Option::get('salescenter', 'default_location_from');
		if (!$optionValue)
		{
			return null;
		}

		if (!CheckSerializedData($optionValue))
		{
			return null;
		}

		$defaultLocationsFrom = unserialize($optionValue, ['allowed_classes' => false]);
		if (!$defaultLocationsFrom)
		{
			return null;
		}

		/**
		 * If it's a single location we must turn it into array
		 */
		if (
			array_key_exists('externalId', $defaultLocationsFrom)
			|| array_key_exists('name', $defaultLocationsFrom)
			|| array_key_exists('address', $defaultLocationsFrom)
		)
		{
			$defaultLocationsFrom = [
				$this->makeLocationCode($defaultLocationsFrom) => $defaultLocationsFrom,
			];
		}

		return $defaultLocationsFrom;
	}

	/**
	 * @param array $location
	 * @return string
	 */
	public function makeLocationCode(array $location): string
	{
		$fieldCollection = (
			isset($location['address']['fieldCollection'])
			&& is_array($location['address']['fieldCollection'])
		)
			? $location['address']['fieldCollection']
			: [];

		return implode('_', [
			(string)$location['sourceCode'],
			(string)$location['externalId'],
			md5(serialize($fieldCollection))
		]);
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

			$result[$this->makeLocationCode($formattedAddress)] = $formattedAddress;
		}

		return $result;
	}
}
