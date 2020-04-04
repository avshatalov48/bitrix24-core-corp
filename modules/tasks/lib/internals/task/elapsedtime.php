<?
/**
 * Class ElapsedTimeTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class ElapsedTimeTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_elapsed_time';
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
			'DATE_START' => array(
				'data_type' => 'datetime',
			),
			'DATE_STOP' => array(
				'data_type' => 'datetime',
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'MINUTES' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SECONDS' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SOURCE' => array(
				'data_type' => 'integer',
			),
			'COMMENT_TEXT' => array(
				'data_type' => 'text',
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\Task',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
		);
	}
}