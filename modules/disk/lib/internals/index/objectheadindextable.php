<?php

namespace Bitrix\Disk\Internals\Index;

use Bitrix\Disk\Configuration;

/**
 * Class ObjectHeadIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ObjectHeadIndex_Query query()
 * @method static EO_ObjectHeadIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ObjectHeadIndex_Result getById($id)
 * @method static EO_ObjectHeadIndex_Result getList(array $parameters = [])
 * @method static EO_ObjectHeadIndex_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectHeadIndex_Collection wakeUpCollection($rows)
 */
class ObjectHeadIndexTable extends BaseIndexTable
{
	public static function getTableName()
	{
		return 'b_disk_object_head_index';
	}

	public static function getMaxIndexSize()
	{
		return Configuration::getMaxHeadIndexSize();
	}
}