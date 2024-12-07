<?php

namespace Bitrix\Sign\Config;

use Bitrix\Main\Config\Option;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Member\Notification\ReminderType;

final class Reminder
{
	private const MINUTES_BEFORE_AGENT_DISABLED = 60 * 24 * 5;

	private static ?self $instance = null;

	public static function instance(): self
	{
		self::$instance ??= new self();

		return self::$instance;
	}

	private function __construct()
	{
	}

	public function usedCustomReminderPeriods(): bool
	{
		return (bool)Option::get('sign', 'USE_CUSTOM_REMINDER_PERIODS', false);
	}

	/**
	 * @return array<value-of<ReminderType>, Item\DateTime\DateIntervalCollection>
	 */
	public function getCustomIntervalsDateIntervals(): array
	{
		if (!$this->usedCustomReminderPeriods())
		{
			return [];
		}

		$firstInterval = $this->getDateIntervalByOptionKey("REMINDER_FIRST_INTERVAL");
		$secondInterval = $this->getDateIntervalByOptionKey("REMINDER_SECOND_INTERVAL");
		$thirdInterval = $this->getDateIntervalByOptionKey("REMINDER_THIRD_INTERVAL");

		if ($firstInterval === null || $secondInterval === null || $thirdInterval === null)
		{
			return [];
		}

		return [
			ReminderType::ONCE_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstInterval,
			),
			ReminderType::TWICE_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstInterval,
				$secondInterval,
			),
			ReminderType::THREE_TIMES_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstInterval,
				$secondInterval,
				$thirdInterval,
			),
		];
	}

	private function getDateIntervalByOptionKey(string $key): ?Item\DateTime\DateInterval
	{
		$option = (string)Option::get('sign', $key);
		if ($option === '')
		{
			return null;
		}

		$parts = explode("-", $option);

		if (count($parts) != 2)
		{
			return null;
		}

		try
		{
			$start = DateTime::createFromFormat('H:i', $parts[0]);
			$end = DateTime::createFromFormat('H:i', $parts[1]);

			return new Item\DateTime\DateInterval($start, $end);
		}
		catch (\Throwable)
		{
			return null;
		}
	}

	public function getNumOfMinutesBeforeAgentDisabled(): int
	{
		return (int)Option::get('sign', 'minutes_before_agent_disabled', self::MINUTES_BEFORE_AGENT_DISABLED);
	}

	public function setNumOfMinutesBeforeAgentDisabled(int $numOfMinutes): void
	{
		Option::set('sign', 'minutes_before_agent_disabled', $numOfMinutes);
	}
}