<?php

namespace Bitrix\Location\Entity\Location\Converter;

use Bitrix\Location\Entity;
use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Location;

class ArrayConverter
{
	public static function convertToArray(Entity\Location $location)
	{
		if($address = $location->getAddress())
		{
			$address = Entity\Address\Converter\ArrayConverter::convertToArray($address, false);
		}

		return [
			'id' => $location->getId(),
			'code' => $location->getCode(),
			'externalId' => $location->getExternalId(),
			'sourceCode' => $location->getSourceCode(),
			'type' => $location->getType(),
			'name' => $location->getName(),
			'languageId' => $location->getLanguageId(),
			'latitude' => $location->getLatitude(),
			'longitude' => $location->getLongitude(),
			'fieldCollection' => self::convertFieldsToArray($location->getAllFieldsValues()),
			'address' => $address
		];
	}

	protected static function convertFieldsToArray(array $fieldsValues): array
	{
		$result = [];

		foreach ($fieldsValues as $type => $value)
		{
			$result[$type] = $value;
		}

		return $result;
	}

	public static function convertParentsToArray(Entity\Location\Parents $parents)
	{
		$result = [];

		foreach ($parents as $location)
		{
			$result[] = ArrayConverter::convertToArray($location);
		}

		return $result;
	}

	public static function convertCollectionToArray(Entity\Location\Collection $collection)
	{
		$result = [];

		foreach ($collection as $location)
		{
			$result[] = self::convertToArray($location);
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Location
	 */
	public static function convertFromArray(array $data): Location
	{
		$result = (new Location())
			->setId((int)$data['id'])
			->setCode((string)$data['code'])
			->setExternalId((string)$data['externalId'])
			->setSourceCode((string)$data['sourceCode'])
			->setType((int)$data['type'])
			->setName((string)$data['name'])
			->setLanguageId((string)$data['languageId'])
			->setLatitude((string)$data['latitude'])
			->setLongitude((string)$data['longitude']);

		if(is_array($data['fieldCollection']))
		{
			foreach ($data['fieldCollection'] as $itemType => $itemValue)
			{
				$result->setFieldValue($itemType, (string)$itemValue);
			}
		}

		return $result;
	}
}