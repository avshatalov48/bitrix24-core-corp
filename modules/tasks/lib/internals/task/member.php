<?
/**
 * Class MemberTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class MemberTable extends Main\Entity\DataManager
{
	public const MEMBER_TYPE_ORIGINATOR = 'O';
	public const MEMBER_TYPE_RESPONSIBLE = 'R';
	public const MEMBER_TYPE_ACCOMPLICE = 'A';
	public const MEMBER_TYPE_AUDITOR = 'U';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_member';
	}

	public static function getObjectClass()
	{
		return MemberObject::class;
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
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'TYPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateType'),
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
			'TASK_FOLLOWED' => array(
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', self::MEMBER_TYPE_AUDITOR)
				)
			),
			'TASK_COWORKED' => array(
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', self::MEMBER_TYPE_ACCOMPLICE)
				)
			),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}

	/**
	 * @return string[]
	 */
	public static function possibleTypes(): array
	{
		return [
			self::MEMBER_TYPE_ORIGINATOR,
			self::MEMBER_TYPE_RESPONSIBLE,
			self::MEMBER_TYPE_ACCOMPLICE,
			self::MEMBER_TYPE_AUDITOR
		];
	}
}