<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class LogTable
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Log_Query query()
 * @method static EO_Log_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Log_Result getById($id)
 * @method static EO_Log_Result getList(array $parameters = [])
 * @method static EO_Log_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Log createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Log_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Log wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Log_Collection wakeUpCollection($rows)
 */

class LogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_log';
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
			'CREATED_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'FIELD' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateField'),
			),
			'FROM_VALUE' => array(
				'data_type' => 'text',
			),
			'TO_VALUE' => array(
				'data_type' => 'text',
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
		);
	}
	/**
	 * Returns validators for FIELD field.
	 *
	 * @return array
	 */
	public static function validateField()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}