<?php

namespace Bitrix\Location\Source;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Address\FieldType;

/**
 * Class AddressLine1Composer
 * @package Bitrix\Location\Source
 * @internal
 */
final class AddressLine1Composer
{
	/**
	 * @param Address $address
	 * @return void
	 */
	public static function composeAddressLine1(Address $address): void
	{
		if (static::isAddressLine1Present($address))
		{
			return;
		}

		$addressLine1 = '';

		if ($address->getFieldValue(FieldType::STREET))
		{
			$addressLine1 = (string)$address->getFieldValue(FieldType::STREET);
		}

		if ($address->getFieldValue(FieldType::BUILDING))
		{
			if ($addressLine1 !== '')
			{
				$addressLine1 .= ', ';
			}

			$addressLine1 .= (string)$address->getFieldValue(FieldType::BUILDING);
		}

		if ($addressLine1 !== '')
		{
			$address->setFieldValue(FieldType::ADDRESS_LINE_1, $addressLine1);
		}
	}

	/**
	 * @param Address $address
	 * @return bool
	 */
	public static function isAddressLine1Present(Address $address): bool
	{
		return $address->isFieldExist(FieldType::ADDRESS_LINE_1);
	}
}
