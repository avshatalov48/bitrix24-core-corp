<?php
namespace Bitrix\Timeman\Helper;

use Bitrix\Main\Type;

class DateTimeHelper
{
	public static function getDateRegExp()
	{
		return '#^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$#'; // eg. 2019-03-23
	}

	public function formatDate($format, $timestamp)
	{
		if (is_object($timestamp))
		{
			if (!($timestamp instanceof Type\DateTime))
			{
				$timestamp = Type\DateTime::createFromPhp(\DateTime::createFromFormat('Y-m-d H:i:s', $timestamp->format('Y-m-d H:i:s')));
			}
		}

		return \FormatDate($format, $timestamp);
	}
}