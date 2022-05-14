<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Tasks\Integration\SocialNetwork;

/**
 * Class CheckListTable
 *
 * @package Bitrix\Tasks\Internals\Task
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ProjectLastActivity_Query query()
 * @method static EO_ProjectLastActivity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ProjectLastActivity_Result getById($id)
 * @method static EO_ProjectLastActivity_Result getList(array $parameters = [])
 * @method static EO_ProjectLastActivity_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_ProjectLastActivity_Collection wakeUpCollection($rows)
 */
class ProjectLastActivityTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_project_last_activity';
	}

	/**
	 * @return string
	 */
	public static function getClass(): string
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
		return array(
			'PROJECT_ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'ACTIVITY_DATE' => [
				'data_type' => 'datetime',
			],
		);
	}

	public static function tryToAdd(int $projectId): void
	{
		$result = static::getList([
			'select' => ['PROJECT_ID'],
			'filter' => ['PROJECT_ID' => $projectId],
		]);
		if (!$result->fetch())
		{
			static::add([
				'PROJECT_ID' => $projectId,
				'ACTIVITY_DATE' => SocialNetwork\Group::getGroupLastActivityDate($projectId),
			]);
		}
	}
}