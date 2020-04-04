<?
/**
 * Class ReminderTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> TASK_ID int mandatory
 * <li> REMIND_DATE datetime mandatory
 * <li> TYPE enum ('D', 'A') optional
 * <li> TRANSPORT enum ('J', 'E') optional
 * <li> RECEPIENT_TYPE enum ('S', 'R', 'O') optional default 'S'
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * <li> TASK reference to {@link \Bitrix\Tasks\TaskTable}
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class ReminderTable extends Main\Entity\DataManager
{
	const TYPE_DEADLINE = "D";
	const TYPE_COMMON = "A";

	const TRANSPORT_JABBER = "J";
	const TRANSPORT_EMAIL = "E";

	const RECEPIENT_TYPE_SELF = "S";
	const RECEPIENT_TYPE_RESPONSIBLE = "R";
	const RECEPIENT_TYPE_ORIGINATOR = "O";

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_reminder';
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
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'REMIND_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'TYPE' => array(
				'data_type' => 'enum',
				'values' => array(
					self::TYPE_DEADLINE,
					self::TYPE_COMMON,
				),
			),
			'TRANSPORT' => array(
				'data_type' => 'enum',
				'values' => array(
					self::TRANSPORT_JABBER,
					self::TRANSPORT_EMAIL,
				),
			),
			'RECEPIENT_TYPE' => array(
				'data_type' => 'enum',
				'values' => array(
					self::RECEPIENT_TYPE_SELF,
					self::RECEPIENT_TYPE_RESPONSIBLE,
					self::RECEPIENT_TYPE_ORIGINATOR,
				),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
		);
	}
}
