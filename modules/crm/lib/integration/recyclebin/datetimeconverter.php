<?php

namespace Bitrix\Crm\Integration\Recyclebin;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

final class DateTimeConverter
{
	private ?DateTime $movedToBin;
	private ?bool $isNeedConvert = null;

	public function __construct(?DateTime $movedToBin)
	{
		$this->movedToBin = $movedToBin;
	}

	public function convert(?string $dateTime): string
	{
		if ($dateTime && $this->needConvert())
		{
			$dateTimeInstance = DateTime::createFromText($dateTime);
			if ($dateTimeInstance === null)
			{
				return $dateTime;
			}

			return $dateTimeInstance->toString();
		}

		return $dateTime;
	}

	public function needConvert(): bool
	{
		if ($this->isNeedConvert !== null)
		{
			return $this->isNeedConvert;
		}

		if (!$this->movedToBin)
		{
			$this->isNeedConvert = false;

			return $this->isNeedConvert;
		}

		$useServerTimeInRecyclebin = Option::get('crm', 'useServerTimeInRecyclebin', null);
		if (!$useServerTimeInRecyclebin)
		{
			$this->isNeedConvert = true;

			return $this->isNeedConvert;
		}

		$useServerTimeInRecyclebinDateTime = DateTime::createFromText($useServerTimeInRecyclebin);

		$this->isNeedConvert = $this->movedToBin->getTimestamp() > $useServerTimeInRecyclebinDateTime?->getTimestamp();

		return $this->isNeedConvert;
	}
}
