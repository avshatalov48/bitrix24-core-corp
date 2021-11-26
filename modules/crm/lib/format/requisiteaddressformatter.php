<?php
namespace Bitrix\Crm\Format;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main;
use Bitrix\Crm\RequisiteAddress;

class RequisiteAddressFormatter extends EntityAddressFormatter
{
	/** @deprecated Use methods of class Bitrix\Crm\Format\AddressFormatter */
	public static function prepareLines(array $fields, array $options = null)
	{
		return parent::prepareLines(RequisiteAddress::mapEntityFields($fields, $options), $options);
	}
	/** @deprecated Use methods of class Bitrix\Crm\Format\AddressFormatter */
	public static function format(array $fields, array $options = null)
	{
		return parent::formatLines(self::prepareLines($fields, $options), $options);
	}
	/** @deprecated Use methods of class Bitrix\Crm\Format\AddressFormatter */
	public static function formatByCountry(array $fields, $countryId, array $options = null)
	{
		$options['FORMAT'] = static::getFormatByCountryId($countryId);

		return EntityAddressFormatter::format($fields, $options);
	}

	/**
	 * @param $countryId
	 * @return int
	 */
	public static function getFormatByCountryId($countryId)
	{
		$countryId = (int)$countryId;
		switch ($countryId)
		{
			case 1:                // ru

			case 4:                // by
			case 14:               // ua
				$format = EntityAddressFormatter::RUS;
				break;
			case 6:                // kz
				$format = EntityAddressFormatter::RUS2;
				break;
			case 46:               // de
			case 110:              // pl
				$format = EntityAddressFormatter::EU;
				break;
			case 77:               // co
			case 122:              // us
				$format = EntityAddressFormatter::USA;
				break;
			default:
				$format = EntityAddressFormatter::Undefined;
		}

		return $format;
	}
}