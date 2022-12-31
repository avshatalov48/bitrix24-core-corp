<?php

namespace Bitrix\Disk\Internals;

/**
 * Class ObjectSaveIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ObjectSaveIndex_Query query()
 * @method static EO_ObjectSaveIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ObjectSaveIndex_Result getById($id)
 * @method static EO_ObjectSaveIndex_Result getList(array $parameters = [])
 * @method static EO_ObjectSaveIndex_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_ObjectSaveIndex_Collection wakeUpCollection($rows)
 */
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
