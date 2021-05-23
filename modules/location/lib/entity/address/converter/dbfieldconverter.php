<?php

namespace Bitrix\Location\Entity\Address\Converter;

use Bitrix\Location\Entity;

/**
 * Class DbFieldConverter
 * @package Bitrix\Location\Entity\Address\Converter
 * @internal
 */
final class DbFieldConverter
{
	/**
	 * Convert Address to DB fields array
	 *
	 * @param Entity\Address $address
	 * @return array
	 */
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
}