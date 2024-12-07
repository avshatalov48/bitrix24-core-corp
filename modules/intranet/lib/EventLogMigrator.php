<?php

namespace Bitrix\Intranet;

use Bitrix\Main\UI\Filter\DateType;

class EventLogMigrator
{
	private const REPLACES = [
		'flt_created_by_id' => 'USER_ID',
		'flt_ip' => 'IP',
		'flt_date' => 'TIMESTAMP'
	];

	private static function convertDateselValue(string $value = ''): string
	{
		return match ($value)
		{
			'days' => DateType::PREV_DAYS,
			"today" => DateType::CURRENT_DAY,
			"yesterday" => DateType::YESTERDAY,
			"tomorrow" => DateType::TOMORROW,
			"exact" => DateType::EXACT,
			"after", "before", "interval" => DateType::RANGE,
			default => DateType::NONE,
		};
	}

	private static function convertFields(array $fields): array
	{
		$result = [];

		$result['EVENT_NAME'] = '';
		$result['USER_ID_label'] = $fields['flt_created_by_name'] ?? '';
		$result['USER_ID'] = $fields['flt_created_by_id'] ?? '';
		$result['IP'] = $fields['flt_ip'] ?? '';

		$result['TIMESTAMP_datesel'] = self::convertDateselValue($fields['flt_date_datesel'] ?? '');
		$result['TIMESTAMP_from'] = $fields['flt_date_from'] ?? '';
		$result['TIMESTAMP_to'] = $fields['flt_date_to'] ?? '';
		$result['TIMESTAMP_days'] = $fields['flt_date_days'] ?? '';

		return $result;
	}

	private static function convertRows(string $rows): string
	{
		$result = '';

		foreach (self::REPLACES as $search => $replace)
		{
			$result = str_replace($search, $replace, empty($result) ? $rows : $result);
		}

		return $result;
	}

	public static function migrateEventLogFilters(): void
	{
		$allUserOptions = \CUserOptions::GetList(null, ['CATEGORY' => 'main.interface.grid', 'NAME' => 'event_list_grid']);

		while ($option = $allUserOptions->Fetch())
		{
			if (empty($option['VALUE']))
			{
				continue;
			}

			$filterList = unserialize($option['VALUE'], ['allowed_classes' => false])['filters'] ?? [];
			$newFilterList = [];

			foreach ($filterList as $id => $filter)
			{
				if (!empty($filter['name']) && !empty($filter['fields']) && !empty($filter['filter_rows']))
				{
					$newFilterList[$id] = [
						'name' => $filter['name'],
						'fields' => self::convertFields($filter['fields']),
						'filter_rows' => self::convertRows($filter['filter_rows']),
					];
				}
			}

			if (!empty($newFilterList))
			{
				\CUserOptions::SetOption(
					'main.ui.filter',
					'INTRANET_EVENT_LOG_FILTER',
					['filters' => $newFilterList],
					false,
					$option['USER_ID']
				);
			}
		}
	}
}