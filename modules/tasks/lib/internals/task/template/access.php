<?php
namespace Bitrix\Tasks\Internals\Task\Template;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class TaskTemplateAccessTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> GROUP_CODE string(50) mandatory
 * <li> ENTITY_ID int mandatory
 * <li> TASK_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Access_Query query()
 * @method static EO_Access_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Access_Result getById($id)
 * @method static EO_Access_Result getList(array $parameters = [])
 * @method static EO_Access_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\Template\EO_Access_Collection wakeUpCollection($rows)
 */

class AccessTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_task_template_access';
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
				'title' => Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_ID_FIELD'),
			),
			'GROUP_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateGroupCode'),
				'title' => Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_GROUP_CODE_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_ENTITY_ID_FIELD'),
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASK_TEMPLATE_ACCESS_ENTITY_TASK_ID_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for GROUP_CODE field.
	 *
	 * @return array
	 */
	public static function validateGroupCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}