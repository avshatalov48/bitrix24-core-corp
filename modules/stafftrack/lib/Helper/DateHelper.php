<?php

namespace Bitrix\StaffTrack\Helper;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\StaffTrack\Dictionary\Option;
use Bitrix\Stafftrack\Integration\Calendar\SettingsProvider;
use Bitrix\StaffTrack\Provider\OptionProvider;
use Bitrix\StaffTrack\Trait\Singleton;

class DateHelper
{
	use Singleton;

	/** @var string  */
	public const DATE_FORMAT = 'd.m.Y';

	/** @var string  */
	public const DATETIME_FORMAT = 'd.m.Y H:i:s';

	/** @var string  */
	public const CLIENT_DATE_FORMAT = 'D M d Y';

	/** @var string  */
	public const CLIENT_DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';

	/** @var string  */
	public const UTC_TIMEZONE_NAME = 'UTC';

	/** @var int[] */
	private array $timezoneOffset = [];

	/** @var int|null  */
	private ?int $serverOffset = null;

	/**
	 * @param DateTime $dateTime
	 * @return DateTime
	 */
	public function getDateUtc(DateTime $dateTime): DateTime
	{
		return DateTime::createFromTimestamp($dateTime->getTimestamp() - $this->getServerOffset());
	}

	/**
	 * @param string|null $date
	 * @param string $format
	 * @return DateTime
	 */
	public function getServerDate(?string $date = null, string $format = self::DATE_FORMAT): DateTime
	{
		try
		{
			$dateTime = new DateTime(
				$date,
				$format,
			);
		}
		catch (ObjectException $e)
		{
			$dateTime = new DateTime(
				null,
				null,
			);
		}
		finally
		{
			return $dateTime;
		}
	}

	/**
	 * @param DateTime $dateTime
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isNotWorkingDay(DateTime $dateTime): bool
	{
		return $this->isHoliday($dateTime) || $this->isWeekend($dateTime);
	}

	/**
	 * @param DateTime $dateTime
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isWeekend(DateTime $dateTime): bool
	{
		$dayIndex = (int)$dateTime->format('w');
		$weekHolidays = SettingsProvider::getInstance()->getWeekHolidays();

		return in_array($dayIndex, $weekHolidays, true);
	}

	/**
	 * @param DateTime $dateTime
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isHoliday(DateTime $dateTime): bool
	{
		$formatDate = $dateTime->format('j.m');
		$yearHolidays = SettingsProvider::getInstance()->getYearHolidays();

		return in_array($formatDate, $yearHolidays, true);
	}

	/**
	 * @param int $userId
	 * @return DateTime
	 * @throws ObjectException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getOffsetDate(int $userId): DateTime
	{
		$userTimezoneOffset = $this->getUserTimezoneOffsetUtc($userId);
		$dateTimezone = new \DateTimeZone(self::UTC_TIMEZONE_NAME);
		$currentTime = new DateTime(null, null, $dateTimezone);

		return DateTime::createFromTimestamp($currentTime->getTimestamp() + $userTimezoneOffset)
			->setTimeZone($dateTimezone)
		;
	}

	/**
	 * @param int $userId
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getUserTimezoneOffsetUtc(int $userId): int
	{
		if (isset($this->timezoneOffset[$userId]))
		{
			return $this->timezoneOffset[$userId];
		}

		$offsetOption = OptionProvider::getInstance()->getOption($userId, Option::TIMEZONE_OFFSET);

		$this->timezoneOffset[$userId] = (int)($offsetOption?->getValue() ?? date('Z'));

		return $this->timezoneOffset[$userId];
	}

	/**
	 * @return int
	 */
	private function getServerOffset(): int
	{
		if ($this->serverOffset === null)
		{
			$this->serverOffset = (new \DateTime())->getOffset();
		}

		return $this->serverOffset;
	}
}
