<?php

namespace Bitrix\Tasks\Replication\Template\Option;

use Bitrix\Tasks\UI;

class Options
{
	public static array $allowedFields = [
		'PERIOD' => true,
		'EVERY_DAY' => true,
		'WORKDAY_ONLY' => true,
		'EVERY_WEEK' => true,
		'WEEK_DAYS' => true,
		'MONTHLY_TYPE' => true,
		'MONTHLY_DAY_NUM' => true,
		'MONTHLY_MONTH_NUM_1' => true,
		'MONTHLY_WEEK_DAY_NUM' => true,
		'MONTHLY_WEEK_DAY' => true,
		'MONTHLY_MONTH_NUM_2' => true,
		'YEARLY_TYPE' => true,
		'YEARLY_DAY_NUM' => true,
		'YEARLY_MONTH_1' => true,
		'YEARLY_WEEK_DAY_NUM' => true,
		'YEARLY_WEEK_DAY' => true,
		'YEARLY_MONTH_2' => true,
		'START_DATE' => true,
		'END_DATE' => true,
		'TIME' => true,
		// deprecated, TIMEZONE_OFFSET parameter is always 0 in new templates
		'TIMEZONE_OFFSET' => true,
		'DAILY_MONTH_INTERVAL' => true,
		'REPEAT_TILL' => true,
		'TIMES' => true,
		'NEXT_EXECUTION_TIME' => true,
		'DEADLINE_OFFSET' => true,
	];

	public static function validate(array $options): array
	{
		foreach ($options as $field => $value)
		{
			if (!isset(static::$allowedFields[$field]))
			{
				unset($options[$field]);
			}
		}

		$options['EVERY_DAY'] = ((int)($options['EVERY_DAY'] ?? null) ?: 1);
		$options['EVERY_WEEK'] = ((int)($options['EVERY_WEEK'] ?? null) ?: 1);
		$options['MONTHLY_DAY_NUM'] = ((int)($options['MONTHLY_DAY_NUM'] ?? null) ?: 1);
		$options['MONTHLY_MONTH_NUM_1'] = ((int)($options['MONTHLY_MONTH_NUM_1'] ?? null) ?: 1);
		$options['MONTHLY_MONTH_NUM_2'] = ((int)($options['MONTHLY_MONTH_NUM_2'] ?? null) ?: 1);
		$options['YEARLY_DAY_NUM'] = ((int)($options['YEARLY_DAY_NUM'] ?? null) ?: 1);

		$options['PERIOD'] = (string)($options['PERIOD'] ?? null);
		$options['WEEK_DAYS'] = ($options['WEEK_DAYS'] ?? null);
		$options['TIME'] = ($options['TIME'] ?? '');
		$options['WORKDAY_ONLY'] = (($options['WORKDAY_ONLY'] ?? null) === 'Y' ? 'Y' : 'N');
		$options['END_DATE'] = ($options['END_DATE'] ?? null);

		$options['MONTHLY_TYPE'] = static::validateTypeSelector($options['MONTHLY_TYPE'] ?? 0);
		$options['YEARLY_TYPE'] = static::validateTypeSelector($options['YEARLY_TYPE'] ?? 0);

		if ($options['PERIOD'] === '')
		{
			$options['PERIOD'] = 'daily';
		}
		if (!is_array($options['WEEK_DAYS']))
		{
			$options['WEEK_DAYS'] = [];
		}

		$time = 3600 * 5; // five hours
		if (trim($options['TIME']) !== '')
		{
			$time = UI::parseTimeAmount($options['TIME'], 'HH:MI');
		}
		$options['TIME'] = UI::formatTimeAmount($time, 'HH:MI');

		// deprecated, TIMEZONE_OFFSET parameter is always 0 in new templates
		if (array_key_exists('TIMEZONE_OFFSET', $options))
		{
			$options['TIMEZONE_OFFSET'] = (int)$options['TIMEZONE_OFFSET'];
		}

		// for old templates
		if (!array_key_exists('REPEAT_TILL', $options) && (string)$options['END_DATE'] !== '')
		{
			$options['REPEAT_TILL'] = 'date';
		}

		$options['DEADLINE_AFTER'] = (int)($options['DEADLINE_AFTER'] ?? null);
		$options['DEADLINE_OFFSET'] = (int)($options['DEADLINE_OFFSET'] ?? null);

		return $options;
	}

	private static function validateTypeSelector(int $type): int
	{
		if ($type < 1 || $type > 2)
		{
			$type = 1;
		}

		return $type;
	}
}