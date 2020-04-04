<?php
namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Internals\Db\SqlHelper;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\DateTime;

final class DeletedLogV2Table extends DataManager
{
	public static function getTableName()
	{
		return 'b_disk_deleted_log_v2';
	}

	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'STORAGE_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'OBJECT_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'TYPE' => [
				'data_type' => 'enum',
				'values' => ObjectTable::getListOfTypeValues(),
				'required' => true,
			],
			'CREATE_TIME' => [
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => function() {
					return new DateTime();
				},
			],
		];
	}

	public static function insertBatch(array $items)
	{
		parent::insertBatch($items);
	}

	public static function upsertBatch(array $items)
	{
		SqlHelper::upsertBatch(static::getTableName(), $items, [
			'USER_ID' => new SqlExpression('VALUES(?#)', 'USER_ID'),
			'CREATE_TIME' => new SqlExpression('VALUES(?#)', 'CREATE_TIME'),
		]);
	}

	public static function deleteOldEntries()
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$quotedTableName = $helper->quote($tableName);

		$deathTime = $helper->addSecondsToDateTime(-365*24*3600);

		$connection->queryExecute("DELETE FROM {$quotedTableName} WHERE CREATE_TIME < {$deathTime}");
	}
}
