<?

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;

//Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class DependenceTable
 *
 * Fields:
 * <ul>
 * <li> TASK_ID int mandatory
 * <li> PARENT_TASK_ID int mandatory
 * <li> DIRECT int optional
 * </ul>
 *
 * @package Bitrix\Tasks
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Dependence_Query query()
 * @method static EO_Dependence_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Dependence_Result getById($id)
 * @method static EO_Dependence_Result getList(array $parameters = [])
 * @method static EO_Dependence_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Dependence_Collection wakeUpCollection($rows)
 */

class DependenceTable extends Main\Entity\DataManager
{
	private static $tableName = '';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		if(static::$tableName != '')
		{
			return static::$tableName;
		}

		return 'b_tasks_task_dep';
	}

	public static function setTableName($tableName)
	{
		static::$tableName = trim((string) $tableName);
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
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				//'title' => Loc::getMessage('TASK_DEP_ENTITY_TASK_ID_FIELD'),
			),
			'PARENT_TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				//'title' => Loc::getMessage('TASK_DEP_ENTITY_PARENT_TASK_ID_FIELD'),
			),
			'DIRECT' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('TASK_DEP_ENTITY_DIRECT_FIELD'),
			),
		);
	}
}