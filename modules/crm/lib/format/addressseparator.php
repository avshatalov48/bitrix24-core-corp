<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
class AddressSeparator
{
	const Undefined = 0;
	const Comma = 1;
	const NewLine = 2;
	const HtmlLineBreak = 3;

	const Dflt = 1;
	const First = 1;
	const Last = 3;

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	public static function getSeparator($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Comma;
		}

		switch($typeID)
		{
			case self::Comma:
				return ', ';
			case self::NewLine:
				return "\n";
			case self::HtmlLineBreak:
				return '<br/>';
			default:
				return '';
		}
	}
}