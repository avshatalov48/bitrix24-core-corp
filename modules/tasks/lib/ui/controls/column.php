<?php

namespace Bitrix\Tasks\Ui\Controls;

class Column
{
	public static function getFieldsForSorting(): array
	{
		return [
			'SORTING',
			'ACTIVITY_DATE',
			'ID',
			'TITLE',
			'DEADLINE',
			'ORIGINATOR_NAME',
			'RESPONSIBLE_NAME',
			'PRIORITY',
			'MARK',
			'TIME_ESTIMATE',
			'ALLOW_TIME_TRACKING',
			'CREATED_DATE',
			'CHANGED_DATE',
			'CLOSED_DATE',
		];
	}

	public static function getFieldsWithMessages(string $prefix = ''): array
	{
		$rows = static::getFieldsForSorting();
		$map = array_map(fn (string $row): string => $prefix . $row, array_combine($rows, $rows));

		foreach (['ACTIVITY_DATE', 'RESPONSIBLE_NAME'] as $sort)
		{
			$map[$sort] .= '_MSGVER_1';
		}

		return $map;
	}
}