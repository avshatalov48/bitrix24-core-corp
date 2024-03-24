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
 * @method static \Bitrix\Tasks\Internals\Task\MemberCollection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\MemberObject wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\MemberCollection wakeUpCollection($rows)
 */
class MemberTable extends TaskDataManager
{
	public const MEMBER_TYPE_ORIGINATOR = 'O';
	public const MEMBER_TYPE_RESPONSIBLE = 'R';
	public const MEMBER_TYPE_ACCOMPLICE = 'A';
	public const MEMBER_TYPE_AUDITOR = 'U';

	public static function getTableName(): string
	{
		return 'b_tasks_member';
	}

	public static function getClass(): string
	{
		return static::class;
	}

	/**
	 * Returns entity map definition.
	 */
	public static function getMap(): array
	{
		return [
			'TASK_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'USER_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'TYPE' => [
				'data_type' => 'string',
				'primary' => true,
				'validation' => [__CLASS__, 'validateType'],
			],

			// references
			'USER' => [
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => ['=this.USER_ID' => 'ref.ID'],
			],
			'TASK' => [
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => ['=this.TASK_ID' => 'ref.ID'],
			],
			'TASK_FOLLOWED' => [
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => [
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => ['?', self::MEMBER_TYPE_AUDITOR],
				],
			],
			'TASK_COWORKED' => [
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => [
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => ['?', self::MEMBER_TYPE_ACCOMPLICE],
				],
			],
		];
	}

	public static function validateType(): array
	{
		return [
			new Main\Entity\Validator\Length(null, 1),
		];
	}

	public static function possibleTypes(): array
	{
		return [
			self::MEMBER_TYPE_ORIGINATOR,
			self::MEMBER_TYPE_RESPONSIBLE,
			self::MEMBER_TYPE_ACCOMPLICE,
			self::MEMBER_TYPE_AUDITOR,
		];
	}

	public static function getObjectClass(): string
	{
		return MemberObject::class;
	}

	public static function getCollectionClass(): string
	{
		return MemberCollection::class;
	}
}