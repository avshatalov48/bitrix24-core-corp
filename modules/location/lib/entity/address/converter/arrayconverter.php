<?php

namespace Bitrix\Location\Entity\Address\Converter;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Location;

final class ArrayConverter
{
	public static function convertToArray(Address $address, $convertLocation = true): array
	{
		$result = [
			'id' => $address->getId(),
			'latitude' => $address->getLatitude(),
			'longitude' => $address->getLongitude(),
			'languageId' => $address->getLanguageId(),
			'fieldCollection' => self::convertFieldsToArray($address->getAllFieldsValues()),
			'links' => self::convertLinksToArray($address)
		];

		if($convertLocation && $location = $address->getLocation())
		{
			$result['location'] = \Bitrix\Location\Entity\Location\Converter\ArrayConverter::convertToArray($location);
		}

		return $result;
	}

	public static function convertFromArray(array $data): Address
	{
		$result = (new Address((string)$data['languageId']))
			->setId((int)$data['id'])
			->setLatitude((string)$data['latitude'])
			->setLongitude((string)$data['longitude']);


		if(is_array($data['fieldCollection']))
		{
			foreach ($data['fieldCollection'] as $itemType => $itemValue)
			{
				$result->setFieldValue((int)$itemType, (string)$itemValue);
			}
		}


		if(is_array($data['links']))
		{
			foreach ($data['links'] as $link)
			{
				$result->addLink((string)$link['entityId'], (string)$link['entityType']);
			}
		}

		if(isset($data['location']))
		{
			if($location = Location::fromArray($data['location']))
			{
				$result->setLocation($location);
			}
		}

		return $result;
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

	protected static function convertLinksToArray(Address $address): array
	{
		$result = [];

		foreach ($address->getLinks() as $link)
		{
			$result[] = [
				'entityId' => $link->getAddressLinkEntityId(),
				'entityType' => $link->getAddressLinkEntityType()
			];
		}

		return $result;
	}
}