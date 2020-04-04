<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
use Bitrix\Crm\LeadAddress;

class LeadAddressFormatter extends EntityAddressFormatter
{
	public static function prepareLines(array $fields, array $options = null)
	{
		return parent::prepareLines(LeadAddress::mapEntityFields($fields, $options), $options);
	}
	public static function format(array $fields, array $options = null)
	{
		return parent::formatLines(self::prepareLines($fields, $options), $options);
	}
}