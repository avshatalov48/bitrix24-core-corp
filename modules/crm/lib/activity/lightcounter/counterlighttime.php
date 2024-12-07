<?php

namespace Bitrix\Crm\Activity\LightCounter;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use CCrmActivityNotifyType;
use CCrmDateTimeHelper;

final class CounterLightTime
{
	private int $defaultOffset;

	public function __construct()
	{
		$this->defaultOffset = (int)Option::get('crm', 'ACTIVITY_DEFAULT_OFFSET_TO_LIGHT_COUNTER_MINUTES', 15);
	}

	public function calculate(CalculateParams $params): DateTime
	{

		$deadline = $params->deadline();
		if ($deadline === false || $deadline === null || CCrmDateTimeHelper::IsMaxDatabaseDate($deadline))
		{
			return CCrmDateTimeHelper::getMaxDatabaseDateObject();
		}
		else
		{
			$deadline = clone $deadline;
		}
		$offsetMinutes = $this->calculateMinutesToLightCounter($params);

		$deadline->add('-PT' . $offsetMinutes . 'M');

		return $deadline;
	}

	private function calculateMinutesToLightCounter(CalculateParams $params): int
	{
		if (!empty($params->offsets()))
		{
			return $this->offsetsToMinutes($params->offsets());
		}

		$notifyMinutes = $this->notifyParamsToMinutes($params->notifyType(), $params->notifyValue());
		if ($notifyMinutes >= 0)
		{
			return $notifyMinutes;
		}

		return $this->defaultOffset;
	}

	/**
	 * @param int[] $pingOffsets
	 * @return int
	 */
	private function offsetsToMinutes(array $pingOffsets): int
	{
		sort($pingOffsets);
		return end($pingOffsets);
	}

	private function notifyParamsToMinutes(int $type, int $value): int
	{
		switch ($type)
		{
			case CCrmActivityNotifyType::Min:
				return $value;
			case CCrmActivityNotifyType::Hour:
				return $value * 60;
			case CCrmActivityNotifyType::Day:
				return $value * 60 * 24;
			default:
				return -1;
		}
	}
}