<?php

namespace Bitrix\Location\Source\Google\Converters;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Address\FieldType;
use Bitrix\Location\Entity\Location;

/**
 * Class ConverterBase
 * @package Bitrix\Location\Source\Google\Converters
 */
abstract class BaseConverter
{
	protected $languageId = '';

	/**
	 * BaseConverter constructor.
	 */
	public function __construct(string $languageId)
	{
		$this->languageId = $languageId;
	}

	/**
	 * @param mixed $data
	 * @return Location|Location\Collection|null
	 */
	abstract public function convert(array $data);

	public function isPostCode(array $types)
	{
		return in_array('postal_code', $types);
	}

	/**
	 * @param string[] $types
	 * @param Location\Type $typesClass
	 * @return int
	 */
	public function convertTypes(array $types, string $typesClass): int
	{
		$result = $typesClass::UNKNOWN;

		foreach($types as $type)
		{
			$result = PlaceTypeConverter::convert($type);

			if(!$typesClass::isValueExist($result))
			{
				$result = $typesClass::UNKNOWN;
				continue;
			}

			if($result !== $typesClass::UNKNOWN)
			{
				break;
			}
		}

		return $result;
	}

	protected function createAddress(array $addressComponents): Address
	{
		$address = new Address($this->languageId);
		$isAddressLine1Present = false;

		foreach ($addressComponents as $item)
		{
			if($type = $this->convertTypes($item['types'], FieldType::class))
			{
				$address->setFieldValue((int)$type, (string)$item['long_name']);
			}

			if($type === FieldType::ADDRESS_LINE_1)
			{
				$isAddressLine1Present = true;
			}
		}

		if(!$isAddressLine1Present)
		{
			$address = $this->composeLine1($address);
		}

		return $address;
	}

	protected function composeLine1(Address $address): Address
	{
		$addressLine1 = '';

		if($address->getFieldValue(FieldType::STREET))
		{
			$addressLine1 = (string)$address->getFieldValue(FieldType::STREET);
		}

		if($address->getFieldValue(FieldType::BUILDING))
		{
			if($addressLine1 !== '')
			{
				$addressLine1 .= ', ';
			}

			$addressLine1 .= (string)$address->getFieldValue(FieldType::BUILDING);
		}

		if($addressLine1 !== '')
		{
			$address->setFieldValue(FieldType::ADDRESS_LINE_1, $addressLine1);
		}

		return $address;
	}
}

