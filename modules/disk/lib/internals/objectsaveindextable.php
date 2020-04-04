<?php

namespace Bitrix\Disk\Internals;

final class ObjectSaveIndexTable extends ObjectTable
{
	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'SEARCH_INDEX' => [
				'data_type' => 'string',
			],
		];
	}
}
