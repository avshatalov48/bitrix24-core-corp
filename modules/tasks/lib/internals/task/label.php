<?php

/**
 * Class TagTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> NAME string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 **/
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Tasks\Internals\TaskTable;
use Exception;

/**
 * Class TagTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Label_Query query()
 * @method static EO_Label_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Label_Result getById($id)
 * @method static EO_Label_Result getList(array $parameters = [])
 * @method static EO_Label_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\TagObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\TagCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\TagObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\TagCollection wakeUpCollection($rows)
 */
class LabelTable extends TaskDataManager
{
	public static function getTableName(): string
	{
		return 'b_tasks_label';
	}

	public static function getTaskRelationName(): string
	{
		return TaskTagTable::getRelationName();
	}

	public static function getRelationAlias(): string
	{
		return 'TASK_TAG';
	}

	public static function getRelationTable(): string
	{
		return 'b_tasks_task_tag';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('TAG_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'default' => 0,
					'title' => Loc::getMessage('LABEL_ENTITY_USER_ID_FIELD'),
				]
			),
			new StringField(
				'NAME',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('TAG_ENTITY_NAME_FIELD'),
				]
			),
			new IntegerField(
				'GROUP_ID',
				[
					'default' => 0,
					'title' => Loc::getMessage('LABEL_ENTITY_GROUP_ID_FIELD'),
				]
			),
			//references
			(new Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new Reference(
				'GROUP',
				WorkgroupTable::class,
				Join::on('this.GROUP_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new Reference(
				'TASK_TAG',
				TaskTagTable::class,
				Join::on('this.ID', 'ref.TAG_ID')
			))->configureJoinType(Join::TYPE_INNER),

			(new ManyToMany(
				'TASKS', TaskTable::class
			))
				->configureTableName('b_tasks_task_tag')
				->configureLocalReference('TAG')
				->configureJoinType(Join::TYPE_INNER)
		];
	}

	/**
	 * @throws ArgumentTypeException
	 */
	public static function validateName(): array
	{
		return [
			new LengthValidator(null, 255),
		];
	}


	public static function getCollectionClass(): string
	{
		return TagCollection::class;
	}

	public static function getObjectClass(): string
	{
		return TagObject::class;
	}

	public static function deleteByFilter(array $filter): Result
	{
		$result = new Result();
		try
		{
			$tagsQuery = static::query();
			$tagsQuery
				->setSelect(['ID'])
				->setFilter($filter);
			$tags = $tagsQuery->exec()->fetchCollection();
			if ($tags->isEmpty())
			{
				return $result;
			}

			foreach ($tags as $tag)
			{
				$tag->delete();
			}
		}
		catch (Exception $exception)
		{
			$result->addError(Error::createFromThrowable($exception));
		}

		return $result;
	}
}