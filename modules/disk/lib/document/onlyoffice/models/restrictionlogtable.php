<?php

namespace Bitrix\Disk\Document\OnlyOffice\Models;

use Bitrix\Disk\Internals\Entity\Query;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

/**
 * Class RestrictionLogTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RestrictionLog_Query query()
 * @method static EO_RestrictionLog_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RestrictionLog_Result getById($id)
 * @method static EO_RestrictionLog_Result getList(array $parameters = [])
 * @method static EO_RestrictionLog_Entity getEntity()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\RestrictionLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_RestrictionLog_Collection createCollection()
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\RestrictionLog wakeUpObject($row)
 * @method static \Bitrix\Disk\Document\OnlyOffice\Models\EO_RestrictionLog_Collection wakeUpCollection($rows)
 */
class RestrictionLogTable extends DataManager
{
	public const STATUS_PENDING = 0;
	public const STATUS_USED = 2;

	public static function getTableName(): string
	{
		return 'b_disk_onlyoffice_restriction_log';
	}

	public static function getObjectClass(): string
	{
		return RestrictionLog::class;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('USER_ID'))
				->configureRequired()
			,
			(new StringField('EXTERNAL_HASH'))
				->configureRequired()
				->configureSize(128)
			,
			(new IntegerField('STATUS'))
				->configureRequired()
				->configureDefaultValue(self::STATUS_PENDING)
			,
			(new DatetimeField('CREATE_TIME'))
				->configureDefaultValue(fn () => new DateTime())
			,
			(new DatetimeField('UPDATE_TIME'))
				->configureDefaultValue(fn () => new DateTime())
			,
		];
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		$result = new EventResult();
		$result->modifyFields([
			'UPDATE_TIME' => new DateTime(),
			'STATUS' => self::STATUS_USED,
		]);

		return $result;
	}

	public static function updateBatch(array $fields, array $filter): void
	{
		$tableName = static::getTableName();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$update = $sqlHelper->prepareUpdate($tableName, $fields);

		$query = new Query(static::getEntity());
		$query->setFilter($filter);
		$query->getQuery();

		$alias = $sqlHelper->quote($query->getInitAlias()) . '.';
		$where = str_replace($alias, '', $query->getWhere());

		$sql = 'UPDATE ' . $tableName . ' SET ' . $update[0] . ' WHERE ' . $where;
		$connection->queryExecute($sql, $update[1]);
	}
}