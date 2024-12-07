<?php

namespace Bitrix\Crm\Integration\location;

use Bitrix\Main\Event;
use Bitrix\Crm\Format\EntityAddressFormatter;
use Bitrix\Main\EventResult;

class Format
{
	public static function onCurrentFormatCodeChanged(Event $params): void
	{
		$locAddressFormatCode = $params->getParameter('formatCode');

		if($crmAddressFormatCode = static::getCrmFormatCode($locAddressFormatCode))
		{
			EntityAddressFormatter::setFormatID($crmAddressFormatCode);
		}
	}

	public static function onInitialFormatCodeSet(): EventResult
	{
		return (new EventResult(EventResult::ERROR));
	}

	public static function getLocationFormatCode(string $crmAddressFormatCode): string
	{
		switch ($crmAddressFormatCode)
		{
			case EntityAddressFormatter::EU:
				$result = 'EU';
				break;

			case EntityAddressFormatter::USA:
				$result = 'US';
				break;

			case EntityAddressFormatter::UK:
				$result = 'UK';
				break;

			case EntityAddressFormatter::RUS2:
				$result = 'RU_2';
				break;

			case EntityAddressFormatter::RUS:
				$result = 'RU';
				break;

			default:
				$result = '';

		}

		return $result;
	}

	public static function getCrmFormatCode(string $locAddressFormatCode): string
	{
		switch ($locAddressFormatCode)
		{
			case 'EU':
				$result = EntityAddressFormatter::EU;
				break;

			case 'US':
				$result = EntityAddressFormatter::USA;
				break;

			case 'UK':
				$result = EntityAddressFormatter::UK;
				break;

			case 'RU_2':
				$result = EntityAddressFormatter::RUS2;
				break;

			case 'RU':
				$result = EntityAddressFormatter::RUS;
				break;

			default:
				$result = EntityAddressFormatter::Undefined;

		}

		return $result;
	}
}
