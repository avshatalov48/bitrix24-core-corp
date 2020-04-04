<?php
// ONLY FOR INTERNAL USE
namespace Bitrix\Tasks\Ui\Controls;

class Column
{
	public static function getFieldsForSorting()
	{
		return self::getFieldsForSortingRaw();
	}

	private static function getFieldsForSortingRaw()
	{
		return array(
			'SORTING',
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
			'CLOSED_DATE'
		);
	}
}