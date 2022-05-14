<?
/**
 * Class TimerTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class TimerTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Timer_Query query()
 * @method static EO_Timer_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Timer_Result getById($id)
 * @method static EO_Timer_Result getList(array $parameters = [])
 * @method static EO_Timer_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Timer_Collection wakeUpCollection($rows)
 */
class TimerTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_timer';
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
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'TIMER_STARTED_AT' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TIMER_ACCUMULATOR' => array(
				'data_type' => 'integer',
				'required' => true,
			),
		);
	}
}