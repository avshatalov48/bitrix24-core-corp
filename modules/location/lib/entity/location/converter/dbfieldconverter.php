<?php

namespace Bitrix\Location\Entity\Location\Converter;

use Bitrix\Location\Entity;
use Bitrix\Location\Entity\Address\Normalizer\Builder;

/**
 * Class Location
 * @package Bitrix\Location\Converter\ToDBFields
 */
class DbFieldConverter
{
	/**
	 * @param Entity\Location $location
	 * @return array
	 */
	public static function convertToDbFields(Entity\Location $location): array
	{
		$result = [];

		if(($location->getId() > 0))
		{
			$result['ID'] = $location->getId();
		}

		$result['CODE'] = (string)$location->getCode();
		$result['EXTERNAL_ID'] = (string)$location->getExternalId();
		$result['SOURCE_CODE'] = (string)$location->getSourceCode();
		$result['TYPE'] = (int)$location->getType();
		$result['LATITUDE'] = (string)$location->getLatitude();
		$result['LONGITUDE'] = (string)$location->getLongitude();

		return $result;
	}

	public static function convertFieldsToDbField(Entity\Address $address): array
	{
		$result = [];

		/** @var Entity\Address\Field $field */
		foreach ($address->getAllFieldsValues() as $type => $value)
		{
			$result[] = [
				'TYPE' => $type,
				'VALUE' => $value,
				'ADDRESS_ID' => $address->getId()
			];
		}

		return $result;
	}

	public static function convertNameToDbFields(Entity\Location $location)
	{
		$normalizer = Builder::build($location->getLanguageId());

		$result = [
			'NAME' => $location->getName(),
			'LANGUAGE_ID' => $location->getLanguageId(),
			'LOCATION_ID' => $location->getId(),
			'NAME_NORMALIZED' => $normalizer->normalize(
				$location->getName()
			)
		];

		return $result;
	}
}