<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\TaskDataManager;

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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Parameter_Query query()
 * @method static EO_Parameter_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Parameter_Result getById($id)
 * @method static EO_Parameter_Result getList(array $parameters = [])
 * @method static EO_Parameter_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Parameter_Collection wakeUpCollection($rows)
 */

class ParameterTable extends TaskDataManager
{
	public const PARAM_SUBTASKS_TIME = 1;
	public const PARAM_SUBTASKS_AUTOCOMPLETE = 2;
	public const PARAM_RESULT_REQUIRED = 3;

	public const PREFIX_PARAM = 'PARAM_';

	/**
	 * @return int[]
	 */
	public static function paramsList(): array
	{
		return [
			self::PARAM_SUBTASKS_TIME,
			self::PARAM_SUBTASKS_AUTOCOMPLETE,
			self::PARAM_RESULT_REQUIRED,
		];
	}

	public static function getLegacyMap(): array
	{
		$constants = (new \ReflectionClass(self::class))->getReflectionConstants();
		$map = [];
		foreach ($constants as $constant)
		{
			if (
				$constant->class === self::class
				&& mb_strrpos($constant->getName(), self::PREFIX_PARAM) === 0
			)
			{
				$map[$constant->getValue()] = $constant->getName();
			}
		}
		return $map;
	}

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