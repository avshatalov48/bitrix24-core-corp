<?php

namespace Bitrix\Location\Entity\Address\Converter;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Location\Type;
use Bitrix\Location\Model\EO_Address;
use Bitrix\Location\Model\EO_AddressField;
use Bitrix\Location\Model\EO_Address_Collection;
use Bitrix\Location\Model\EO_AddressField_Collection;
use Bitrix\Location\Model\EO_AddressLink;
use Bitrix\Location\Model\EO_AddressLink_Collection;

final class OrmConverter
{
	public static function convertToOrm(Address $address): EO_Address
	{
		$ormAddress = new EO_Address();
		$addressId = $address->getId();
		$ormAddress->setId($addressId)
			->setLatitude($address->getLatitude())
			->setLongitude($address->getLongitude())
			->setLanguageId($address->getLanguageId());

		if($location = $address->getLocation())
		{
			$ormAddress->setLocationId($location->getId());
		}
	}

	public static function convertLinksToOrm(Address $address): EO_AddressLink_Collection
	{
		$result = new EO_AddressLink_Collection();

		/** @var Address\IAddressLink $link */
		foreach ($address->getLinks() as $link)
		{
			$result->add(
				(new EO_AddressLink())
					->setAddressId($address->getId())
					->setEntityId($link->getAddressLinkEntityId())
					->setEntityType($link->getAddressLinkEntityType())
			);
		}

		return $result;
	}

	public static function convertFieldsToOrm(Address $address): EO_AddressField_Collection
	{
		$result = new EO_AddressField_Collection();
		$normalizer = Address\Normalizer\Builder::build($address->getLanguageId());
		$parents = null;

		/** @var Address\Field $field */
		foreach ($address->getFieldCollection() as $field)
		{
			$value = $field->getValue();
			$result->add(
				(new EO_AddressField())
					->setType($field->getType())
					->setValue($field->getValue())
					->setAddressId($address->getId())
					->setValueNormalized( $normalizer->normalize($value))
			);
		}

		return $result;
	}

	public static function convertFromOrm(EO_Address $ormAddress): Address
	{
		$result = new Address($ormAddress->getLanguageId());
		$result->setId($ormAddress->getId())
			->setLatitude($ormAddress->getLatitude())
			->setLongitude($ormAddress->getLongitude());

		/** @var Address\Field $field */
		foreach ($ormAddress->getFields() as $field)
		{
			$result->setFieldValue((int)$field->getType(), (string)$field->getValue());
		}

		if($ormLocation = $ormAddress->getLocation())
		{
			$location = \Bitrix\Location\Entity\Location\Factory\OrmFactory::createLocation(
				$ormLocation,
				$ormAddress->getLanguageId()
			);

			if($location)
			{
				$result->setLocation($location);
			}
		}

		if($links = $ormAddress->getLinks())
		{
			/** @var EO_AddressLink $link */
			foreach ($links as $link)
			{
				$result->addLink($link->getEntityId(), $link->getEntityType());
			}
		}

		return $result;
	}

	public static function convertCollectionFromOrm(EO_Address_Collection $collection): Address\AddressCollection
	{
		$result = new Address\AddressCollection();

		foreach ($collection as $item)
		{
			$result->addItem(self::convertFromOrm($item));
		}

		return $result;
	}
}