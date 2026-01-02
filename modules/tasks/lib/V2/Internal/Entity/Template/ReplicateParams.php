<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Template;

use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;
use Bitrix\Tasks\V2\Internal\Entity\ValueObjectInterface;

class ReplicateParams implements ValueObjectInterface
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?Period $period = null,
		public readonly ?string $everyDay = null,
		public readonly ?string $workdayOnly = null,
		public readonly ?string $dailyMonthInterval = null,
		public readonly ?string $everyWeek = null,
		public readonly ?string $monthlyType = null,
		public readonly ?string $monthlyDayNum = null,
		public readonly ?string $monthlyMonthNum1 = null,
		public readonly ?string $monthlyWeekDayNum = null,
		public readonly ?string $monthlyWeekDay = null,
		public readonly ?string $monthlyMonthNum2 = null,
		public readonly ?string $yearlyType = null,
		public readonly ?string $yearlyDayNum = null,
		public readonly ?string $yearlyMonth1 = null,
		public readonly ?string $yearlyWeekDayNum = null,
		public readonly ?string $yearlyWeekDay = null,
		public readonly ?string $yearlyMonth2 = null,
		public readonly ?string $time = null,
		public readonly ?string $timezoneOffset = null,
		public readonly ?string $startDate = null,
		public readonly ?string $repeatTill = null,
		public readonly ?string $endDate = null,
		public readonly ?string $times = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'period' => $this->period?->value,
			'everyDay' => $this->everyDay,
			'workdayOnly' => $this->workdayOnly,
			'dailyMonthInterval' => $this->dailyMonthInterval,
			'everyWeek' => $this->everyWeek,
			'monthlyType' => $this->monthlyType,
			'monthlyDayNum' => $this->monthlyDayNum,
			'monthlyMonthNum1' => $this->monthlyMonthNum1,
			'monthlyWeekDayNum' => $this->monthlyWeekDayNum,
			'monthlyWeekDay' => $this->monthlyWeekDay,
			'monthlyMonthNum2' => $this->monthlyMonthNum2,
			'yearlyType' => $this->yearlyType,
			'yearlyDayNum' => $this->yearlyDayNum,
			'yearlyMonth1' => $this->yearlyMonth1,
			'yearlyWeekDayNum' => $this->yearlyWeekDayNum,
			'yearlyWeekDay' => $this->yearlyWeekDay,
			'yearlyMonth2' => $this->yearlyMonth2,
			'time' => $this->time,
			'timezoneOffset' => $this->timezoneOffset,
			'startDate' => $this->startDate,
			'repeatTill' => $this->repeatTill,
			'endDate' => $this->endDate,
			'times' => $this->times,
		];
	}

	public static function mapFromArray(array $props): static
	{
		return new self(
			period: static::mapBackedEnum($props, 'period', Period::class),
			everyDay: static::mapString($props, 'everyDay'),
			workdayOnly: static::mapString($props, 'workdayOnly'),
			dailyMonthInterval: static::mapString($props, 'dailyMonthInterval'),
			everyWeek: static::mapString($props, 'everyWeek'),
			monthlyType: static::mapString($props, 'monthlyType'),
			monthlyDayNum: static::mapString($props, 'monthlyDayNum'),
			monthlyMonthNum1: static::mapString($props, 'monthlyMonthNum1'),
			monthlyWeekDayNum: static::mapString($props, 'monthlyWeekDayNum'),
			monthlyWeekDay: static::mapString($props, 'monthlyWeekDay'),
			monthlyMonthNum2: static::mapString($props, 'monthlyMonthNum2'),
			yearlyType: static::mapString($props, 'yearlyType'),
			yearlyDayNum: static::mapString($props, 'yearlyDayNum'),
			yearlyMonth1: static::mapString($props, 'yearlyMonth1'),
			yearlyWeekDayNum: static::mapString($props, 'yearlyWeekDayNum'),
			yearlyWeekDay: static::mapString($props, 'yearlyWeekDay'),
			yearlyMonth2: static::mapString($props, 'yearlyMonth2'),
			time: static::mapString($props, 'time'),
			timezoneOffset: static::mapString($props, 'timezoneOffset'),
			startDate: static::mapString($props, 'startDate'),
			repeatTill: static::mapString($props, 'repeatTill'),
			endDate: static::mapString($props, 'endDate'),
			times: static::mapString($props, 'times'),
		);
	}
}
