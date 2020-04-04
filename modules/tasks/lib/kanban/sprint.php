<?php
namespace Bitrix\Tasks\Kanban;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Tasks\Integration\SocialNetwork;

Loc::loadMessages(__FILE__);

class SprintTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_sprint';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			)),
			'GROUP_ID' => new Entity\IntegerField('GROUP_ID', array(
				'title' => 'GROUP_ID',
				'required' => true
			)),
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', array(
				'title' => 'CREATED_BY_ID'
			)),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', array(
				'title' => 'MODIFIED_BY_ID'
			)),
			'START_TIME' => new Entity\DatetimeField('START_TIME', array(
				'title' => 'START_TIME',
				'required' => true
			)),
			'FINISH_TIME' => new Entity\DatetimeField('FINISH_TIME', array(
				'title' => 'FINISH_TIME'
			))
		);
	}

	/**
	 * Temporary method for checking rights to start new sprint.
	 * @param int $groupId Group id.
	 * @return bool
	 */
	private static function canStart($groupId)
	{
		return SocialNetwork\Group::can(
			$groupId,
			SocialNetwork\Group::ACTION_CREATE_TASKS
		);
	}

	/**
	 * Gets default stages for each next sprint.
	 * @param int $entityId Entity id for copy last view.
	 * @return array
	 */
	public static function getStages($entityId = 0)
	{
		static $stages = [];

		// try to get last stages for same group id
		if ($entityId > 0)
		{
			// get current group id
			$res = self::getList([
				'select' => [
					'ID', 'GROUP_ID'
				],
				'filter' => [
					'ID' => $entityId
				],
				'order' => [
					'ID' => 'desc'
				],
				'limit' => 1
			]);
			if ($row = $res->fetch())
			{
				// get last sprint for this group
				$res = self::getList([
					'select' => [
						'ID'
					],
					'filter' => [
						'!ID' => $row['ID'],
						'GROUP_ID' => $row['GROUP_ID']
					],
					'order' => [
						'ID' => 'desc'
					]
				]);
				if ($row = $res->fetch())
				{
					// get stages for last sprint
					$res = StagesTable::getList([
						'select' => [
							'*'
						],
						'filter' => [
							'ENTITY_TYPE' => StagesTable::WORK_MODE_SPRINT,
							'ENTITY_ID' => $row['ID']
						],
						'order' => [
							'SORT' => 'asc'
						]
					]);
					while ($row = $res->fetch())
					{
						$stages[] = [
							'TITLE' => $row['TITLE'],
							'COLOR' => $row['COLOR'],
							'SYSTEM_TYPE' => $row['SYSTEM_TYPE']
						];
					}
				}
			}
		}

		if ($stages)
		{
			return $stages;
		}

		$stages = [
			'NEW' => [
				'COLOR' => '00C4FB',
				'SYSTEM_TYPE' => 'NEW'
			],
			'WORK' => [
				'COLOR' => '47D1E2',
				'SYSTEM_TYPE' => 'WORK'
			],
			'FINISH' => [
				'COLOR' => '75D900',
				'SYSTEM_TYPE' => 'FINISH'
			]
		];

		return $stages;
	}

	/**
	 * Close current sprint, if it exists.
	 * @param int $groupId Group id.
	 * @return bool True, if was closed.
	 */
	public static function closeCurrent($groupId)
	{
		$groupId = intval($groupId);
		$currentTime = new DateTime();
		$res = self::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'GROUP_ID' => $groupId,
				'>FINISH_TIME' => $currentTime
			],
			'order' => [
				'FINISH_TIME' => 'desc'
			]
		]);
		if ($row = $res->fetch())
		{
			self::update(
				$row['ID'],
				[
					'FINISH_TIME' => $currentTime
				]
			);
			return true;
		}
		return false;
	}

	/**
	 * Creates next sprint in the group.
	 * @param int $groupId Group id.
	 * @param DateTime $endDate Date for ending result sprint.
	 * @return Entity\AddResult
	 */
	public static function createNext($groupId, DateTime $endDate)
	{
		$currentTime = new DateTime();
		$currentUserId = \Bitrix\Tasks\Util\User::getId();
		$result = new Entity\AddResult();
		$groupId = intval($groupId);

		// check rights
		if (!self::canStart($groupId))
		{
			$result->addError(new Entity\EntityError(
				Loc::getMessage('TASKS_SPRINT_ERROR_CANT_CREATE'),
				'CANT_CREATE_SPRINT'
			));
			return $result;
		}

		// end date must be higher current
		if ($endDate < $currentTime)
		{
			$result->addError(new Entity\EntityError(
				Loc::getMessage('TASKS_SPRINT_ERROR_END_DATE_WRONG'),
				'END_DATE_WRONG'
			));
			return $result;
		}

		// close current and add next
		self::closeCurrent($groupId);
		$result = self::add([
			'CREATED_BY_ID' => $currentUserId,
			'MODIFIED_BY_ID' => $currentUserId,
			'GROUP_ID' => $groupId,
			'START_TIME' => $currentTime,
			'FINISH_TIME' => $endDate
		]);

		return $result;
	}

	/**
	 * Gets necessary sprint form group (selected or last).
	 * @param int $groupId Group id.
	 * @param int $sprintId Sprint id, or 0 for current sprint.
	 * @return array
	 */
	public static function getSprint($groupId, $sprintId = 0)
	{
		$sprint = [];
		$groupId = intval($groupId);
		$sprintId = intval($sprintId);

		$filter = [
			'GROUP_ID' => $groupId
		];
		if ($sprintId)
		{
			$filter['ID'] = $sprintId;
		}

		$res = self::getList([
			'filter' => $filter,
			'order' => [
				'FINISH_TIME' => 'desc'
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			$sprint = $row;
		}

		return $sprint;
	}

	/**
	 * Gets all sprints of the group.
	 * @param int $groupId Group id.
	 * @return array
	 */
	public static function getAllSprints($groupId)
	{
		$sprints = [];
		$groupId = (int)$groupId;

		$res = self::getList([
			'filter' => [
				'GROUP_ID' => $groupId
			],
			'order' => [
				'FINISH_TIME' => 'desc'
			]
		]);
		while ($row = $res->fetch())
		{
			$sprints[$row['ID']] = $row;
		}

		return $sprints;
	}

	/**
	 * Add the task to default stage of the sprint.
	 * @param int $sprintId Sprint id.
	 * @param int $taskId Task id.
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 */
	public static function addToSprint($sprintId, $taskId)
	{
		StagesTable::setWorkMode(
			StagesTable::WORK_MODE_SPRINT
		);
		$defaultStageId = StagesTable::getDefaultStageId($sprintId);

		if (!$taskId || !$defaultStageId)
		{
			$result = new Entity\AddResult();
			$result->addError(new Entity\EntityError(
				Loc::getMessage('TASKS_SPRINT_ERROR_CANT_ADD_TO_SPRINT'),
				'CANT_ADD_TO_SPRINT'
  			));
			return $result;
		}

		$result = TaskStageTable::add([
			'TASK_ID' => $taskId,
			'STAGE_ID' => $defaultStageId
]		);

		return $result;
	}

	/**
	 * On sprint delete.
	 * @param Entity\Event $event Event.
	 * @return void
	 */
	public static function onDelete(Entity\Event $event)
	{
		$primary = $event->getParameter('id');

		if ($primary['ID'])
		{
			StagesTable::setWorkMode(
				StagesTable::WORK_MODE_SPRINT
			);
			$res = StagesTable::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'ENTITY_TYPE' => StagesTable::WORK_MODE_SPRINT,
					'ENTITY_ID' => $primary['ID']
				]
]			);
			while ($row = $res->fetch())
			{
				StagesTable::systemDelete($row['ID']);
			}
		}
	}
}