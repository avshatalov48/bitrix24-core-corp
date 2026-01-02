<?php

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class AllowedAgentsTimeManager
{
	use Singleton;

	private const USE_TIME_LIMIT_OPTION_NAME = 'repeat_sale_use_time_limit';
	private const TIME_LIMIT_START_HOUR_OPTION_NAME = 'repeat_sale_time_limit_start_hour';
	private const TIME_LIMIT_END_HOUR_OPTION_NAME = 'repeat_sale_time_limit_end_hour';

	public function isAllowedTime(): bool
	{
		if (!$this->isUseTimeLimit())
		{
			return true;
		}

		$portalDateTime = $this->getCurrentDateTime();
		$currentHour = (int)$portalDateTime->format('G');

		$startHour = $this->getTimeLimitStartHour();
		$endHour = $this->getTimeLimitEndHour();

		if ($startHour < $endHour)
		{
			return $currentHour >= $startHour && $currentHour < $endHour;
		}

		return $currentHour >= $startHour || $currentHour < $endHour;
	}

	public function getAllowedTime(): DateTime
	{
		$portalDateTime = $this->getCurrentDateTime();

		if (!$this->isUseTimeLimit())
		{
			return $portalDateTime;
		}

		$startHour = $this->getTimeLimitStartHour();
		$endHour = $this->getTimeLimitEndHour();
		$currentHour = (int)$portalDateTime->format('G');

		$crossesMidnight = $endHour < $startHour;

		if ($crossesMidnight)
		{
			if ($currentHour >= $startHour || $currentHour < $endHour)
			{
				return $portalDateTime;
			}

			return $portalDateTime->add('+1 day')->setTime($startHour, 0, 0);
		}

		if ($currentHour >= $startHour && $currentHour <= $endHour)
		{
			return $portalDateTime;
		}

		if ($currentHour < $startHour)
		{
			return $portalDateTime->setTime($startHour, 0, 0);
		}

		return $portalDateTime->add('+1 day')->setTime($startHour, 0, 0);
	}

	public function isUseTimeLimit(): bool
	{
		return Option::get('crm', self::USE_TIME_LIMIT_OPTION_NAME, 'Y') === 'Y';
	}

	public function setUseTimeLimit(bool $useTimeLimit): void
	{
		Option::set('crm', self::USE_TIME_LIMIT_OPTION_NAME, $useTimeLimit ? 'Y' : 'N');
	}

	public function getAllowedIntervalInSeconds(): int
	{
		$startHour = $this->getTimeLimitStartHour();
		$endHour = $this->getTimeLimitEndHour();

		$startDate = (new DateTime())->setTime($startHour, 0, 0);
		$endDate = (new DateTime())->setTime($endHour, 0, 0);

		if ($endHour < $startHour)
		{
			$endDate->add('+1 day');
		}

		return $endDate->getTimestamp() - $startDate->getTimestamp();
	}

	protected function getTimeLimitStartHour(): int
	{
		return (int)Option::get('crm', self::TIME_LIMIT_START_HOUR_OPTION_NAME, 22);
	}

	protected function getTimeLimitEndHour(): int
	{
		return (int)Option::get('crm', self::TIME_LIMIT_END_HOUR_OPTION_NAME, 8);
	}

	protected function getCurrentDateTime(): DateTime
	{
		return (new DateTime())->disableUserTime();
	}
}
