<?php

namespace Bitrix\Disk\Internals\Index;

use Bitrix\Disk\Configuration;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\DateTime;

/**
 * Class ObjectExtendedIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ObjectExtendedIndex_Query query()
 * @method static EO_ObjectExtendedIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ObjectExtendedIndex_Result getById($id)
 * @method static EO_ObjectExtendedIndex_Result getList(array $parameters = [])
 * @method static EO_ObjectExtendedIndex_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\Index\EO_ObjectExtendedIndex_Collection wakeUpCollection($rows)
 */
class ObjectExtendedIndexTable extends BaseIndexTable
{
	const STATUS_SHORT    = 2;
	const STATUS_EXTENDED = 3;

	public static function getMap()
	{
		return array_merge(parent::getMap(), [
			(new Fields\EnumField('STATUS'))
				->configureValues([
					static::STATUS_SHORT,
					static::STATUS_EXTENDED,
				])
				->configureDefaultValue(static::STATUS_SHORT)
		]);
	}

	public static function getTableName()
	{
		return 'b_disk_object_extended_index';
	}

	public static function getMaxIndexSize()
	{
		return Configuration::getMaxExtendedIndexSize();
	}

	public static function upsert($objectId, $searchIndex, $status = self::STATUS_SHORT)
	{
		$objectId = (int)$objectId;
		$searchIndex = trim($searchIndex);

		static::merge([
			'OBJECT_ID' => $objectId,
			'SEARCH_INDEX' => $searchIndex,
			'UPDATE_TIME' => new DateTime(),
			'STATUS' => $status,
		]);
	}

	public static function changeStatus($objectId, $status)
	{
		$objectId = (int)$objectId;

		static::merge([
			'OBJECT_ID' => $objectId,
			'UPDATE_TIME' => new DateTime(),
			'STATUS' => $status,
		]);
	}
}