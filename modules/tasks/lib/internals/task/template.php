<?php
/**
 * Class TemplateTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

use Bitrix\Main\Entity\EnumField;

Loc::loadMessages(__FILE__);

use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Internals\Task\Template\TemplateCollection;
use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateMemberTable;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\Template\TemplateTagTable;
use Bitrix\Tasks\Util\UserField;

/**
 * Class TemplateTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Template_Query query()
 * @method static EO_Template_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Template_Result getById($id)
 * @method static EO_Template_Result getList(array $parameters = [])
 * @method static EO_Template_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\TemplateCollection wakeUpCollection($rows)
 */
class TemplateTable extends Main\Entity\DataManager
{
	public static function getObjectClass(): string
	{
		return TemplateObject::class;
	}

	public static function getCollectionClass(): string
	{
		return TemplateCollection::class;
	}

	/**
	 * Returns userfield entity code, to make userfields work with orm
	 *
	 * @return string
	 */
	public static function getUfId()
	{
		return UserField\Task\Template::getEntityCode();
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_template';
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

			// common with TaskTable
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TITLE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateTitle'),
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_TITLE_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'text',
			),
			'DESCRIPTION_IN_BBCODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
			),
			new EnumField('PRIORITY', [
				'values' => array_merge(
					array_values(Priority::getAll()),
					array_map('strval', array_values(Priority::getAll()))
				),
				'default_value' => (string)Priority::AVERAGE,
			]),

			// wtf? status in template?
			'STATUS' => array(
				'data_type' => 'string',
				'default_value' => '1',
				'validation' => array(__CLASS__, 'validateStatus'),
			),
			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_ASSIGNEE_ID_FIELD'),
			),
			'TIME_ESTIMATE' => array( // in seconds
				'data_type' => 'integer',
				'default_value' => '0',
			),
			'REPLICATE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_CREATED_BY_FIELD'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'ALLOW_CHANGE_DEADLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'ALLOW_TIME_TRACKING' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'TASK_CONTROL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'ADD_IN_REPORT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'MATCH_WORK_TIME' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'MULTITASK' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N',
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('TASKS_TASK_TEMPLATE_ENTITY_SITE_ID_FIELD'),
			),

			// template-specific
			'REPLICATE_PARAMS' => array(
				'data_type' => 'text',
			),
			'TAGS' => array(
				'data_type' => 'text',
			),
			'ACCOMPLICES' => array(
				'data_type' => 'text',
			),
			'AUDITORS' => array(
				'data_type' => 'text',
			),
			'RESPONSIBLES' => array(
				'data_type' => 'text',
			),
			'DEPENDS_ON' => array(
				'data_type' => 'text',
			),
			'DEADLINE_AFTER' => array(
				'data_type' => 'integer',
			),
			'START_DATE_PLAN_AFTER' => array(
				'data_type' => 'integer',
			),
			'END_DATE_PLAN_AFTER' => array(
				'data_type' => 'integer',
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
			),

			// template parameters
			'TPARAM_TYPE' => array(
				'data_type' => 'integer',
				//'validation' => array(__CLASS__, 'validateType'),
			),
			'TPARAM_REPLICATION_COUNT' => array(
				'data_type' => 'integer',
				'default_value' => 0,
			),
			'ZOMBIE' => array(
				'data_type' => 'text',
				'default_value' => 'N'
			),

			// deprecated
			'FILES' => array(
				'data_type' => 'string',
			),

			// references
			'CREATOR' => [
				'data_type' => 'Bitrix\Main\User',
				'reference' => ['=this.CREATED_BY' => 'ref.ID']
			],
			'RESPONSIBLE' => [
				'data_type' => 'Bitrix\Main\User',
				'reference' => ['=this.RESPONSIBLE_ID' => 'ref.ID']
			],
			(new OneToMany("MEMBERS", TemplateMemberTable::class, "TEMPLATE")),
			(new OneToMany("TAG_LIST", TemplateTagTable::class, "TEMPLATE")),
			(new OneToMany("DEPENDENCIES", TemplateDependenceTable::class, "TEMPLATE")),
			(
				new Reference(
					'SCENARIO',
					\Bitrix\Tasks\Internals\Task\Template\ScenarioTable::class,
					Join::on('this.ID', 'ref.TEMPLATE_ID')
				)
			)->configureJoinType('left'),
			(new Reference(
				'CHECKLIST_DATA',
				\Bitrix\Tasks\Internals\Task\Template\CheckListTable::getEntity(),
				['this.ID' => 'ref.TEMPLATE_ID'],
			))->configureJoinType(Join::TYPE_LEFT)
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
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for PRIORITY field.
	 *
	 * @return array
	 */
	public static function validatePriority()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for STATUS field.
	 *
	 * @return array
	 */
	public static function validateStatus()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
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
			new Main\Entity\Validator\Length(null, 50),
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
			new Main\Entity\Validator\Length(null, 2),
		);
	}
}