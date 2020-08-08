<?php

namespace Bitrix\Location\Entity\Location\Converter;


use Bitrix\Location\Entity\Location;
use Bitrix\Location\Model\EO_AddressField;
use Bitrix\Location\Model\EO_LocationField_Collection;
use Bitrix\Location\Model\LocationFieldTable;

final class OrmConverter
{
	public static function convertFieldsToOrm(Location $location): EO_LocationField_Collection
	{
		$result = LocationFieldTable::createCollection();

		/** @var Location\Field $field */
		foreach ($location->getFieldCollection() as $field)
		{
			$result->add(
				(LocationFieldTable::createObject())
					->setType($field->getType())
					->setValue($field->getValue())
					->setLocationId($location->getId())
			);
		}

		return $result;
	}
}