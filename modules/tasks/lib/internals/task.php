<?php

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SiteTable;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\Mark;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\Internals\Task\UtsTasksTaskTable;
use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\UserField;
use Bitrix\Tasks\V2\Internal\Model\TaskChatTable;
use Bitrix\Main\Text\Emoji;

Loc::loadMessages(__FILE__);

/**
 * Class TaskTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Task_Query query()
 * @method static EO_Task_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Task_Result getById($id)
 * @method static EO_Task_Result getList(array $parameters = [])
 * @method static EO_Task_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\TaskObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\TaskCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\TaskObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\TaskCollection wakeUpCollection($rows)
 */
class TaskTable extends TaskDataManager
{
	public static function getUfId(): string
	{
		return UserField\Task::getEntityCode();
	}

	public static function getTableName(): string
	{
		return 'b_tasks';
	}

	public static function getClass(): string
	{
		return self::class;
	}

	public static function getMap(): array
	{
		return [

			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new StringField('TITLE'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 255))
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_TITLE_FIELD'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),

			(new TextField('DESCRIPTION'))
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_DESCRIPTION_FIELD'))
				->addSaveDataModifier([Emoji::class, 'encode'])
				->addFetchDataModifier([Emoji::class, 'decode']),

			(new BooleanField('DESCRIPTION_IN_BBCODE'))
				->configureValues('N', 'Y')
				->configureDefaultValue('Y'),

			(new EnumField('PRIORITY'))
				->configureValues(array_merge(array_values(Priority::getAll()), array_values(Priority::getAll(true))))
				->configureDefaultValue(Priority::AVERAGE),

			(new EnumField('STATUS'))
				->configureValues(array_merge(array_values(Status::getAll()), array_values(Status::getAll(true))))
				->configureDefaultValue(Status::PENDING)
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_STATUS_FIELD')),

			(new IntegerField('STAGE_ID'))
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_STAGE_ID_FIELD')),

			(new IntegerField('RESPONSIBLE_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_ASSIGNEE_ID_FIELD')),

			new DateTimeField('DATE_START'),

			new IntegerField('DURATION_PLAN'),
			new IntegerField('DURATION_FACT'),

			(new EnumField('DURATION_TYPE'))
				->configureValues(array_values(TimeUnitType::getAll()))
				->configureDefaultValue(TimeUnitType::DAY),

			(new IntegerField('TIME_ESTIMATE'))
				->configureDefaultValue(0),

			(new BooleanField('REPLICATE'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N'),

			(new DateTimeField('DEADLINE'))
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_DEADLINE_FIELD')),

			new DateTimeField('START_DATE_PLAN'),
			new DateTimeField('END_DATE_PLAN'),

			(new IntegerField('CREATED_BY'))
				->configureRequired()
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_CREATED_BY_FIELD')),

			new DateTimeField('CREATED_DATE'),

			new IntegerField('CHANGED_BY'),

			new DateTimeField('CHANGED_DATE'),

			new IntegerField('STATUS_CHANGED_BY'),

			new DateTimeField('STATUS_CHANGED_DATE'),

			new IntegerField('CLOSED_BY'),

			new DateTimeField('CLOSED_DATE'),

			new DateTimeField('ACTIVITY_DATE'),

			(new StringField('GUID'))
				->addValidator(new LengthValidator(null, 50)),

			(new StringField('XML_ID'))
				->addValidator(new LengthValidator(null, 200))
				->configureTitle(Loc::getMessage('TASKS_ENTITY_XML_ID_FIELD')),

			(new EnumField('MARK'))
				->configureValues(Mark::getMarks()),

			(new BooleanField('ALLOW_CHANGE_DEADLINE'))
				->configureValues('N', 'Y'),

			(new BooleanField('ALLOW_TIME_TRACKING'))
				->configureValues('N', 'Y'),

			(new BooleanField('TASK_CONTROL'))
				->configureValues('N', 'Y'),

			(new BooleanField('ADD_IN_REPORT'))
				->configureValues('N', 'Y'),

			(new IntegerField('GROUP_ID'))
				->configureDefaultValue(0),

			new IntegerField('PARENT_ID'),

			new IntegerField('FORUM_TOPIC_ID'),

			(new BooleanField('MULTITASK'))
				->configureValues('N', 'Y'),

			(new StringField('SITE_ID'))
				->configureRequired()
				->addValidator(new LengthValidator(null, 2)),

			new IntegerField('FORKED_BY_TEMPLATE_ID'),

			(new BooleanField('ZOMBIE'))
				->configureValues('N', 'Y'),

			(new BooleanField('MATCH_WORK_TIME'))
				->configureValues('N', 'Y'),

			(new BooleanField('IS_REGULAR'))
				->configureValues('N', 'Y')
				->configureDefaultValue(null),

			(new IntegerField('OUTLOOK_VERSION'))
				->configureDefaultValue(1),

			(new StringField('EXCHANGE_ID')),

			(new StringField('EXCHANGE_MODIFIED')),

			(new StringField('DECLINE_REASON')),

			(new IntegerField('DEADLINE_COUNTED')),


			(new Reference(
				'CREATOR',
				UserTable::class,
				Join::on('this.CREATED_BY', 'ref.ID')
			)),

			(new Reference(
				'RESPONSIBLE',
				UserTable::class,
				Join::on('this.RESPONSIBLE_ID', 'ref.ID')
			)),

			(new Reference(
				'PARENT',
				self::class,
				Join::on('this.PARENT_ID', 'ref.ID')
			)),

			(new Reference(
				'SITE',
				SiteTable::class,
				Join::on('this.SITE_ID', 'ref.LID')
			)),

			(new Reference(
				'GROUP',
				WorkgroupTable::class,
				Join::on('this.GROUP_ID', 'ref.ID')
			)),

			(new Reference(
				'SCENARIO',
				ScenarioTable::class,
				Join::on('this.ID', 'ref.TASK_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'REGULAR',
				RegularParametersTable::class,
				Join::on('this.ID', 'ref.TASK_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'UTS_DATA',
				UtsTasksTaskTable::getEntity(),
				Join::on('this.ID', 'ref.VALUE_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'CHECKLIST_DATA',
				CheckListTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'FLOW_TASK',
				FlowTaskTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'CHAT_TASK',
				TaskChatTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'RESULTS',
				ResultTable::class,
				Join::on('this.ID', 'ref.TASK_ID')
			)),

			(new Reference(
				'MEMBERS',
				MemberTable::class,
				Join::on('this.ID', 'ref.TASK_ID')
			)),

			(new OneToMany(
				'FAVORITE_TASK',
				FavoriteTable::class,
				'TASK'
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany(
				'MEMBER_LIST',
				MemberTable::class,
				'TASK'
			))
				->configureJoinType(Join::TYPE_INNER),

			(new OneToMany(
				'RESULT',
				ResultTable::class,
				'TASK'
			))
				->configureJoinType(Join::TYPE_LEFT),

			(new ManyToMany(
				'TAG_LIST',
				LabelTable::class
			))
				->configureLocalReference('TASK')
				->configureRemoteReference('TAG')
				->configureTableName(TaskTagTable::getTableName())
				->configureJoinType(Join::TYPE_INNER),
		];
	}

	public static function getObjectClass(): string
	{
		return TaskObject::class;
	}

	public static function getCollectionClass(): string
	{
		return TaskCollection::class;
	}
}
