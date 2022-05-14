<?
/**
 * Class RelatedTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class RelatedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Related_Query query()
 * @method static EO_Related_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Related_Result getById($id)
 * @method static EO_Related_Result getList(array $parameters = [])
 * @method static EO_Related_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Related_Collection wakeUpCollection($rows)
 */
class RelatedTable extends TaskDataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_dependence';
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
			),
			'DEPENDS_ON_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
		);
	}
}