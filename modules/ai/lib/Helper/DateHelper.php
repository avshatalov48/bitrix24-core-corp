<?php declare(strict_types=1);

namespace Bitrix\AI\Helper;

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;

class DateHelper
{
	public string $dateTimeFormat;

	public function getDateTimeFormat(): string
	{
		if (empty($this->dateTimeFormat))
		{
			$culture = Context::getCurrent()?->getCulture();

			$this->dateTimeFormat = $culture?->getShortDateFormat() . ' ' . $culture?->getLongTimeFormat();
		}

		return $this->dateTimeFormat;
	}

	public function getBitrixDateTimeFromPhp(string $dateTime): DateTime
	{
		return DateTime::createFromPhp(
			\DateTime::createFromFormat($this->getDateTimeFormat(), $dateTime)
		);
	}
}
