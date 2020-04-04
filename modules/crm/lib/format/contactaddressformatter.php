<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
use Bitrix\Crm\ContactAddress;

class ContactAddressFormatter extends EntityAddressFormatter
{
	public static function prepareLines(array $fields, array $options = null)
	{
		return parent::prepareLines(ContactAddress::mapEntityFields($fields, $options), $options);
	}
	public static function format(array $fields, array $options = null)
	{
		return parent::formatLines(self::prepareLines($fields, $options), $options);
	}
}