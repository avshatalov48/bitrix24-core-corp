<?php

/**
 * Class TasksTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\EnumField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Driver;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Internals\Task\Mark;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\Internals\Task\UtsTasksTaskTable;
use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\UserField;

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
	/**
	 * Returns userfield entity code, to make userfields work with orm
	 *
	 * @return string
	 */
	public static function getUfId()
	{
		return UserField\Task::getEntityCode();
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_TITLE_FIELD'),
				'save_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getSaveModificator'),
				'fetch_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getFetchModificator'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_DESCRIPTION_FIELD'),
				'save_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getSaveModificator'),
				'fetch_data_modification' => array('\Bitrix\Main\Text\Emoji', 'getFetchModificator'),
			),
			'DESCRIPTION_IN_BBCODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
			),
			(new EnumField('PRIORITY'))
				->configureDefaultValue(Priority::AVERAGE)
				->configureValues(
					array_merge(array_values(Priority::getAll()), array_values(Priority::getAll(true)))
				)
			,
			(new EnumField('STATUS'))
				->configureDefaultValue(Status::PENDING)
				->configureValues(
					array_merge(array_values(Status::getAll()), array_values(Status::getAll(true)))
				)
				->configureTitle(Loc::getMessage('TASKS_TASK_ENTITY_STATUS_FIELD'))
			,
			'STAGE_ID' => array(
				'data_type' => 'integer',
				'required' => false,
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_STAGE_ID_FIELD'),
//                'validation' => array(__CLASS__, 'validateStageId'), // if need validate on exists
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_ASSIGNEE_ID_FIELD'),
			),
			'DATE_START' => array(
				'data_type' => 'datetime',
			),
			'DURATION_PLAN' => array(
				'data_type' => 'integer',
			),
			'DURATION_FACT' => array(
				'data_type' => 'integer',
			),
			(new EnumField('DURATION_TYPE'))
				->configureDefaultValue(TimeUnitType::DAY)
				->configureValues(array_values(TimeUnitType::getAll()))
			,
			'TIME_ESTIMATE' => array( // in seconds
				'data_type' => 'integer',
				'default_value' => '0',
			),
			'REPLICATE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			new DateTimeField('DEADLINE', array(
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_DEADLINE_FIELD'),
			)),
			new DateTimeField('START_DATE_PLAN'),
			new DateTimeField('END_DATE_PLAN'),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_ENTITY_CREATED_BY_FIELD'),
			),
			new DateTimeField('CREATED_DATE'),
			'CHANGED_BY' => array(
				'data_type' => 'integer',
			),
			new DateTimeField('CHANGED_DATE'),
			'STATUS_CHANGED_BY' => array(
				'data_type' => 'integer',
			),
			new DateTimeField('STATUS_CHANGED_DATE'),
			'CLOSED_BY' => array(
				'data_type' => 'integer',
			),
			new DateTimeField('CLOSED_DATE'),
			new DateTimeField('ACTIVITY_DATE'),
			'GUID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateGuid'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('TASKS_ENTITY_XML_ID_FIELD'),
			),
			(new EnumField('MARK'))
				->configureValues(Mark::getMarks())
			,
			'ALLOW_CHANGE_DEADLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'ALLOW_TIME_TRACKING' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'TASK_CONTROL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'ADD_IN_REPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'default_value' => '0'
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'FORUM_TOPIC_ID' => array(
				'data_type' => 'integer',
			),
			'MULTITASK' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
			),
			'FORKED_BY_TEMPLATE_ID' => array(
				'data_type' => 'integer',
			),
			'ZOMBIE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'MATCH_WORK_TIME' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			(new Entity\BooleanField('IS_REGULAR'))
				->configureValues('N', 'Y')
				->configureDefaultValue(null),

			// references
			'CREATOR' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY' => 'ref.ID')
			),
			'RESPONSIBLE' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.RESPONSIBLE_ID' => 'ref.ID')
			),
			'PARENT' => array(
				'data_type' => 'Task',
				'reference' => array('=this.PARENT_ID' => 'ref.ID')
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID')
			),
			'MEMBERS' => array(
				'data_type' => 'Bitrix\Tasks\Internals\Task\MemberTable',
				'reference' => array(
					'=this.ID'=>'ref.TASK_ID'
				)
			),
			'RESULTS' => [
				'data_type' => ResultTable::class,
				'reference' => [
					'=this.ID'=>'ref.TASK_ID'
				],
			],
			(
				new Reference(
					'SCENARIO',
					ScenarioTable::class,
					Join::on('this.ID', 'ref.TASK_ID')
				)
			)->configureJoinType(Join::TYPE_LEFT),
			(
				new Reference(
					'REGULAR',
				RegularParametersTable::class,
					Join::on('this.ID', 'ref.TASK_ID')
				)
			)->configureJoinType(Join::TYPE_LEFT),

			// socialnetwork module should be present
			'GROUP' => array(
				'data_type' => 'Bitrix\Socialnetwork\Workgroup',
				'reference' => array('=this.GROUP_ID' => 'ref.ID')
			),

			// obsolete, but required
			'OUTLOOK_VERSION' => array(
				'data_type' => 'integer',
				'default_value' => '1',
			),

			(new OneToMany("MEMBER_LIST", MemberTable::class, "TASK"))->configureJoinType("inner"),
			//todo
			(new ManyToMany("TAG_LIST", LabelTable::class))
				->configureLocalReference("TASK")
				->configureRemoteReference('TAG')
				->configureTableName('b_tasks_task_tag')
				->configureJoinType("inner"),

			'EXCHANGE_ID' => [
				'data_type' => 'string',
			],
			'EXCHANGE_MODIFIED' => [
				'data_type' => 'string',
			],
			'DECLINE_REASON' => [
				'data_type' => 'string',
			],
			'DEADLINE_COUNTED' => [
				'data_type' => 'integer',
			],
			(new Reference(
				'UTS_DATA',
				UtsTasksTaskTable::getEntity(),
				['this.ID' => 'ref.VALUE_ID']
			))->configureJoinType(Join::TYPE_LEFT),

			(new OneToMany(
				'RESULT',
				ResultTable::class,
				'TASK'
			))->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'CHECKLIST_DATA',
				CheckListTable::getEntity(),
				['this.ID' => 'ref.TASK_ID'],
			))->configureJoinType(Join::TYPE_LEFT),

			(new Reference(
				'FLOW_TASK',
				FlowTaskTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID'),
			))->configureJoinType(Join::TYPE_LEFT),
		);
	}
	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 */
	public static function validateTitle()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for GUID field.
	 *
	 * @return array
	 */
	public static function validateGuid()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Entity\Validator\Length(null, 200),
		);
	}
	/**
	 * Returns validators for EXCHANGE_ID field.
	 *
	 * @return array
	 */
	public static function validateExchangeId()
	{
		return array(
			new Entity\Validator\Length(null, 196),
		);
	}
	/**
	 * Returns validators for EXCHANGE_MODIFIED field.
	 *
	 * @return array
	 */
	public static function validateExchangeModified()
	{
		return array(
			new Entity\Validator\Length(null, 196),
		);
	}
	/**
	 * Returns validators for SITE_ID field.
	 *
	 * @return array
	 */
	public static function validateSiteId()
	{
		return array(
			new Entity\Validator\Length(null, 2),
		);
	}

	/**
	 * @param array $data
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function insert(array $data)
	{
		$fields = self::getEntity()->getFields();

		$id = 0;
		$siteId = SITE_ID;
		$insertData = [];
		foreach ($data as $field => $value)
		{
			if (!array_key_exists($field, $fields))
			{
				continue;
			}

			if ($field === 'ID')
			{
				$id = (int)$value;
				continue;
			}
			if ($field === 'SITE_ID')
			{
				$siteId = $value;
			}

			if (
				$fields[$field] instanceof DateTimeField
				&& is_numeric($value)
			)
			{
				$insertData[$field] = DateTime::createFromTimestampGmt($value);
			}
			else
			{
				$insertData[$field] = $value;
			}
		}

		if (!$id)
		{
			return;
		}

		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();
		$sql = $helper->getInsertIgnore(self::getTableName(), ' (ID, SITE_ID)', " VALUES ({$id}, '{$siteId}')");
		$connection->queryExecute($sql);

		$taskObject = self::getByPrimary($id)->fetchObject();
		if (!$taskObject)
		{
			return;
		}
		$driver = Driver::getInstance();
		$userFieldManager = $driver->getUserFieldManager();
		$attachedObjects = $userFieldManager->getAttachedObjectByEntity(
			'TASKS_TASK',
			$id,
			\Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()
		);
		$ids = array_map(static function ($el): int {
			return $el->getId();
		}, $attachedObjects);
		$insertData[\Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()] = $ids;

		foreach ($insertData as $field => $value)
		{
			$taskObject->set($field, $value);
		}

		try
		{
			$res = $taskObject->save();
		}
		catch (\Exception $e)
		{

		}
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
