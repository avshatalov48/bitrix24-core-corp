<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
use Bitrix\Crm\ContactAddress;

class ContactAddressFormatter extends EntityAddressFormatter
{
	/** @deprecated Use methods of class Bitrix\Crm\Format\AddressFormatter */
	public static function prepareLines(array $fields, array $options = null)
	{
		return parent::prepareLines(ContactAddress::mapEntityFields($fields, $options), $options);
	}
	/** @deprecated Use methods of class Bitrix\Crm\Format\AddressFormatter */
	public static function format(array $fields, array $options = null)
	{
		return parent::formatLines(self::prepareLines($fields, $options), $options);
	}
}