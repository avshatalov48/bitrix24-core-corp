<?php

namespace Bitrix\Location\Entity\Address\Converter;

use Bitrix\Location\Entity;
use Bitrix\Location\Entity\Address\Normalizer;

final class DbFieldConverter
{
	public static function convertToDbField(Entity\Address $address): array
	{
		$locationId = 0;

		if($location = $address->getLocation())
		{
			$locationId = $location->getId();
		}

		return [
			'ID' => (int)$address->getId(),
			'LOCATION_ID' => (int)$locationId,
			'LANGUAGE_ID' => (string)$address->getLanguageId(),
			'LATITUDE' => (string)$address->getLatitude(),
			'LONGITUDE' => (string)$address->getLongitude()
		];
	}

	public static function convertFieldsToDbField(Entity\Address $address): array
	{
		$result = [];
		$normalizer = Normalizer\Builder::build($address->getLanguageId());

		/** @var Entity\Address\Field $field */
		foreach ($address->getAllFieldsValues() as $type => $value)
		{
			$result[] = [
				'TYPE' => $type,
				'VALUE' => $value,
				'VALUE_NORMALIZED' => $normalizer->normalize($value),
				'ADDRESS_ID' => $address->getId()
			];
		}

		return $result;
	}

	public static function convertLinksToDbField(Entity\Address $address): array
	{
		$result = [];

		/** @var Entity\Address\IAddressLink $link */
		foreach ($address->getLinks() as $link)
		{
			$result[] = [
				'ADDRESS_ID' => $address->getId(),
				'ENTITY_ID' => $link->getAddressLinkEntityId(),
				'ENTITY_TYPE' => $link->getAddressLinkEntityType()
			];
		}

		return $result;
	}
}