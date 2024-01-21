<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Internals\TaskTable;

/**
 * Class RegularParametersTable
 *
 * Fields:
 * <ul>
 * <li> TASK_ID int mandatory
 * <li> REGULAR_PARAMETERS text mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RegularParameters_Query query()
 * @method static EO_RegularParameters_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RegularParameters_Result getById($id)
 * @method static EO_RegularParameters_Result getList(array $parameters = [])
 * @method static EO_RegularParameters_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\RegularParametersCollection wakeUpCollection($rows)
 */

class RegularParametersTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_regular_parameters';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('TASK_ID'))
				->configureRequired(),
			(new ArrayField('REGULAR_PARAMETERS'))
				->configureSerializationJson()
				->configureRequired(),
			(new DatetimeField('START_TIME'))
				->configureDefaultValue(null),
			(new DateField('START_DAY'))
				->configureDefaultValue(null),
			(new BooleanField('NOTIFICATION_SENT'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N'),
			(new ReferenceField(
				'TASK',
				TaskTable::getEntity(),
				['this.TASK_ID' => 'ref.ID']
			))->configureJoinType(Join::TYPE_LEFT),
		];
	}

	public static function getObjectClass(): string
	{
		return RegularParametersObject::class;
	}

	public static function getCollectionClass(): string
	{
		return RegularParametersCollection::class;
	}

	public static function getByTaskId(int $taskId): ?RegularParametersObject
	{
		$query = static::query();
		$query
			->setSelect(['*'])
			->where('TASK_ID', $taskId);

		return $query->exec()->fetchObject();
	}

	public static function deleteByTaskId(int $taskId): void
	{
		static::getByTaskId($taskId)?->delete();
	}
}