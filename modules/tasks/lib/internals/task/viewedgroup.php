<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Comments\Viewed\Enum;
use Bitrix\Tasks\Comments\Viewed\Group;
use Bitrix\Tasks\Internals\TaskDataManager;
use Bitrix\Tasks\MemberTable;

/**
 * Class ViewedTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ViewedGroup_Query query()
 * @method static EO_ViewedGroup_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ViewedGroup_Result getById($id)
 * @method static EO_ViewedGroup_Result getList(array $parameters = [])
 * @method static EO_ViewedGroup_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ViewedGroup_Collection wakeUpCollection($rows)
 */
class ViewedGroupTable extends TaskDataManager
{

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_viewed_group';
	}

	/**
	 * @return false|string
	 */
	public static function getClass()
	{
		return static::class;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [

			'GROUP_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'MEMBER_TYPE' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'VIEWED_DATE' => [
				'data_type' => 'datetime',
				'required' => true,
			]
		];
	}

	/**
	 * @param array $data
	 * @throws Main\DB\SqlQueryException
	 */
	public static function upsert(array $data): void
	{
		$now = new DateTime();

		$typeId = (int)($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : Enum::UNDEFINED;
		$userId = isset($data['USER_ID']) ? (int)$data['USER_ID'] : 0;
		$groupId = isset($data['GROUP_ID']) ? (int)$data['GROUP_ID'] : 0;
		$memberType = in_array($data['MEMBER_TYPE'], MemberTable::possibleTypes()) ? $data['MEMBER_TYPE']: Group::MEMBER_TYPE_UNDEFINED;

		$insertFields = [

			'TYPE_ID' => $typeId,
			'USER_ID' => $userId,
			'GROUP_ID' => $groupId,
			'VIEWED_DATE' => $now,
			'MEMBER_TYPE' => $memberType,
		];

		$updateFields = [
			'VIEWED_DATE' => $now,
		];

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			static::getTableName(),
			[
				'MEMBER_TYPE',
				'USER_ID',
				'GROUP_ID',
				'TYPE_ID'
			],
			$insertFields,
			$updateFields
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
}