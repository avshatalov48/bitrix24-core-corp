<?php
/**
 * Class MemberTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class MemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Member_Query query()
 * @method static EO_Member_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Member_Result getById($id)
 * @method static EO_Member_Result getList(array $parameters = [])
 * @method static EO_Member_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\MemberObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Member_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\MemberObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Member_Collection wakeUpCollection($rows)
 */
class MemberTable extends TaskDataManager
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