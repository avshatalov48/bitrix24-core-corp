<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class TaskParameterTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(50) mandatory
 * <li> VALUE string(10) optional
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

class ParameterTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_task_parameter';
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
				//'title' => Loc::getMessage('TASK_PARAMETER_ENTITY_ID_FIELD'),
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('TASK_PARAMETER_ENTITY_TASK_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TASK_PARAMETER_ENTITY_CODE_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateValue'),
				//'title' => Loc::getMessage('TASK_PARAMETER_ENTITY_VALUE_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue()
	{
		return array(
			new Main\Entity\Validator\Length(null, 10),
		);
	}
}