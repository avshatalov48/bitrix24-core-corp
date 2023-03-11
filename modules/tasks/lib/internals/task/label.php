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

use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Tasks\Internals\TaskTable;


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
 * @method static \Bitrix\Tasks\Internals\Task\EO_Label createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Label_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Label wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Label_Collection wakeUpCollection($rows)
 */
class LabelTable extends TaskDataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
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

	public static function getRelationTable()
	{
		return 'b_tasks_task_tag';
	}

	public static function getClass()
	{
		return static::class;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
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
			))->configureJoinType('left'),

			(new Reference(
				'GROUP',
				WorkgroupTable::class,
				Join::on('this.GROUP_ID', 'ref.ID')
			))->configureJoinType('left'),

			(new Reference(
				'TASK_TAG',
				TaskTagTable::class,
				Join::on('this.ID', 'ref.TAG_ID')
			))->configureJoinType('inner'),

			(new ManyToMany(
				'TASKS', TaskTable::class
			))
				->configureTableName('b_tasks_task_tag')
				->configureLocalReference('TAG')
				->configureJoinType('inner')
		];
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName(): array
	{
		return [
			new LengthValidator(null, 255),
		];
	}
}