<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\TasksMobile\Dto\Stage;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\Socialnetwork;
use Bitrix\Tasks\Integration\Socialnetwork\Group;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\Exception\ModuleNotFoundException;
use Bitrix\Mobile\Trait\PublicErrorsTrait;
use CTasks;

final class StageProvider
{
	use PublicErrorsTrait;

	private const STAGE_NAME_REQUIRED_ERROR_CODE = 1;
	private const EDITING_PROHIBITED_ERROR_CODE = 2;
	private const STAGE_UPDATE_ERROR_CODE = 3;
	private const CREATED_STAGE_NOT_FOUND_ERROR_CODE = 4;
	private const DELETION_PROHIBITED_ERROR_CODE = 5;
	private const DELETING_STAGE_WITH_TASKS_ERROR_CODE = 6;
	private const STAGE_DELETE_ERROR_CODE = 7;
	private const STAGES_SORT_ORDER_SAVE_ERROR_CODE = 8;
	private const STAGE_CREATE_ERROR_CODE = 9;

	private string $workMode;
	private int $userId;
	private ?TaskRequestFilter $searchParams;
	private ?int $projectId = null;
	private array $extra;
	private ?int $taskId = null;

	/**
	 * @param int $userId
	 * @param TaskRequestFilter|null $searchParams
	 * @param array $extra
	 */
	function __construct(
		int $userId,
		?TaskRequestFilter $searchParams = null,
		array $extra = [],
	)
	{
		$this->userId = $userId;
		$this->searchParams = ($searchParams ?? TaskRequestFilter::make(['ownerId' => $this->userId]));
		$this->extra = $extra;
	}

	public function getPlannerStages(?int $projectId = null): Result
	{
		$this->workMode = StagesTable::WORK_MODE_USER;
		$this->projectId = $projectId;

		$stages = $this->getStages();
		$canEdit = $this->searchParams->ownerId === $this->userId;
		$canMoveStage = $this->verifyCanMoveStage();

		$result = new Result();
		$result->setData([
			'canEdit' => $canEdit,
			'stages' => $stages,
			'canMoveStage' => $canMoveStage,
		]);

		return $result;
	}

	public function getDeadlineStages(?int $projectId = null): Result
	{
		$this->workMode = StagesTable::WORK_MODE_TIMELINE;
		$this->projectId = $projectId;

		$stages = $this->getStages();
		$canEdit = false;
		$canMoveStage = $this->verifyCanMoveStage();

		$result = new Result();
		$result->setData([
			'canEdit' => $canEdit,
			'stages' => $stages,
			'canMoveStage' => $canMoveStage,
		]);

		return $result;
	}

	public function getProjectStages(int $projectId, ?int $taskId): Result
	{
		$this->workMode = StagesTable::WORK_MODE_GROUP;
		$this->projectId = $projectId;
		$this->taskId = $taskId;

		$stages = $this->getStages();
		$canEdit = $this->hasEditProjectStagesPermission();
		$canMoveStage = $this->verifyCanMoveStage();

		$result = new Result();
		$result->setData([
			'canEdit' => $canEdit,
			'stages' => $stages,
			'canMoveStage' => $canMoveStage,
		]);

		return $result;
	}

	public function getKanbanInfoByWorkMode(int $projectId, int $taskId, string $workMode): Result
	{
		if ($workMode === StagesTable::WORK_MODE_GROUP)
		{
			return $this->getProjectStages($projectId, $taskId);
		}

		if ($workMode === StagesTable::WORK_MODE_TIMELINE)
		{
			return $this->getDeadlineStages($projectId);
		}

		if ($workMode === StagesTable::WORK_MODE_USER)
		{
			return $this->getPlannerStages($projectId);
		}

		return (new Result())->setData([]);
	}

	public function addStage(?int $projectId, string $name, string $color, ?int $afterId = null): Result
	{
		$result = new Result();
		$this->projectId = $projectId;
		$this->workMode = isset($this->projectId) ? StagesTable::WORK_MODE_GROUP : StagesTable::WORK_MODE_USER;
		if (empty($name))
		{
			$result->addError(new Error(Loc::getMessage('STAGE_NAME_REQUIRED_ERROR'), self::STAGE_NAME_REQUIRED_ERROR_CODE));

			return $result;
		}
		if ($this->workMode === StagesTable::WORK_MODE_GROUP
			&& !$this->hasEditProjectStagesPermission())
		{
			$result->addError(new Error(Loc::getMessage('EDITING_PROHIBITED_ERROR'), self::EDITING_PROHIBITED_ERROR_CODE));

			return $result;
		}
		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($this->workMode);
		$fields = array(
			'ID' => 0,
			'COLOR' => $color,
			'TITLE' => $name,
			'ENTITY_ID' => $this->projectId ?? $this->userId,
		);
		if (isset($afterId))
		{
			$fields['AFTER_ID'] = $afterId;
		}
		$res = StagesTable::updateByCode($fields['ID'], $fields);
		StagesTable::setWorkMode($prevWorkMode);
		if ($res && $res->isSuccess())
		{
			$newStageId = (int)($res->getId());
			$stages = $this->getStages();
			foreach ($stages as $stage)
			{
				if ($stage->id === $newStageId)
				{
					$result->setData([
						'stage' => $stage,
					]);

					return $result;
				}
			}

			$result->addError(new Error(Loc::getMessage('CREATED_STAGE_NOT_FOUND_ERROR'), self::CREATED_STAGE_NOT_FOUND_ERROR_CODE));
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('STAGE_ADD_ERROR'), self::STAGE_CREATE_ERROR_CODE));
		}

		return $result;
	}

	private function stageHasTasks(int $stageId, int $stagesEntityId)
	{
		if ($stagesEntityId === $this->searchParams->ownerId)
		{
			$params = [
				'select' => ['ID'],
				'navigate' => [
					'NAV_PARAMS' => [
						'nTopCount' => 1
					]
				],
				'filter' => [
					'STAGES_ID' => $stageId,
					'MEMBER' => $stagesEntityId
				]
			];
		}
		else
		{
			$params = [
				'select' => ['ID'],
				'navigate' => [
					'NAV_PARAMS' => [
						'nTopCount' => 1
					]
				],
				'filter' => [
					'STAGE_ID' => StagesTable::getStageIdByCode(
						$stageId,
						$stagesEntityId
					),
					'GROUP_ID' => $stagesEntityId,
					'CHECK_PERMISSIONS' => 'N'
				]
			];
		}

		[$rows, $res] = \CTaskItem::fetchList(
			$this->searchParams->ownerId,
			($params['order'] ?? []),
			($params['filter'] ?? []),
			($params['navigate'] ?? []),
			($params['select'] ?? [])
		);

		return !empty($rows);
	}

	public function updateStage(int $stageId, ?int $projectId, string $name, string $color): Result
	{
		$result = new Result();
		$this->projectId = $projectId;
		$this->workMode = isset($this->projectId) ? StagesTable::WORK_MODE_GROUP : StagesTable::WORK_MODE_USER;
		if (empty($name))
		{
			$result->addError(new Error(Loc::getMessage('STAGE_NAME_REQUIRED_ERROR'), self::STAGE_NAME_REQUIRED_ERROR_CODE));

			return $result;
		}
		if (!$this->hasEditStagesPermission([$stageId]))
		{
			$result->addError(new Error(Loc::getMessage('EDITING_PROHIBITED_ERROR'), self::EDITING_PROHIBITED_ERROR_CODE));

			return $result;
		}
		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($this->workMode);
		$fields = array(
			'ID' => $stageId,
			'COLOR' => $color,
			'TITLE' => $name,
			'ENTITY_ID' => $this->projectId ?? $this->userId,
		);
		$res = StagesTable::updateByCode($fields['ID'], $fields);
		StagesTable::setWorkMode($prevWorkMode);
		if (!$res || !$res->isSuccess())
		{
			$result->addError(new Error(Loc::getMessage('STAGE_UPDATE_ERROR'),self::STAGE_UPDATE_ERROR_CODE));
		}

		return $result;
	}

	public function deleteStage(int $id, ?int $projectId = null): Result
	{
		$result = new Result();
		$this->projectId = $projectId;
		$this->workMode = isset($this->projectId) ? StagesTable::WORK_MODE_GROUP : StagesTable::WORK_MODE_USER;
		$stagesEntityId = $this->projectId ?? $this->userId;
		if (!$this->hasEditStagesPermission([$id]))
		{
			$errors = $this->markErrorsAsPublic([new Error(Loc::getMessage('STAGE_DELETION_PROHIBITED_ERROR'), self::DELETION_PROHIBITED_ERROR_CODE)]);
			$result->addErrors($errors);

			return $result;
		}
		if ($this->stageHasTasks($id, $this->projectId ?? $this->userId))
		{
			$errors = $this->markErrorsAsPublic([new Error(Loc::getMessage('STAGE_WITH_TASKS_DELETION_PROHIBITED_ERROR'), self::DELETING_STAGE_WITH_TASKS_ERROR_CODE)]);

			$result->addErrors($errors);
			return $result;
		}

		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($this->workMode);
		$res = StagesTable::delete(
			$id,
			$stagesEntityId
		);
		StagesTable::setWorkMode($prevWorkMode);
		if (!$res || !$res->isSuccess())
		{
			$result->addError(new Error(Loc::getMessage('STAGE_DELETION_ERROR'), self::STAGE_DELETE_ERROR_CODE));
		}

		return $result;
	}

	public function updateStagesSortOrder(?int $projectId, array $stagesOrder = []): Result
	{
		$result = new Result();
		$this->projectId = $projectId;
		$this->workMode = isset($this->projectId) ? StagesTable::WORK_MODE_GROUP : StagesTable::WORK_MODE_USER;
		if ($this->hasEditStagesPermission($stagesOrder))
		{
			$this->workMode = isset($this->projectId) ? StagesTable::WORK_MODE_GROUP : StagesTable::WORK_MODE_USER;
			$prevWorkMode = StagesTable::getWorkMode();
			StagesTable::setWorkMode($this->workMode);
			foreach ($stagesOrder as $index=>$stageId)
			{
				$afterId = $index > 0 ? $stagesOrder[$index - 1] : 0;
				$updateResult = StagesTable::updateByCode($stageId, array(
					'AFTER_ID' => $afterId,
					'ENTITY_ID' => $this->projectId ?? $this->searchParams->ownerId,
				));
				if (!isset($updateResult) || !$updateResult->isSuccess())
				{
					$result->addError(new Error(Loc::getMessage('STAGES_SORT_ORDER_UPDATE_ERROR'), self::STAGES_SORT_ORDER_SAVE_ERROR_CODE));

					return $result;
				}
			}
			StagesTable::setWorkMode($prevWorkMode);
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('EDITING_PROHIBITED_ERROR'), self::EDITING_PROHIBITED_ERROR_CODE));
		}

		return $result;
	}

	private function stagesInWhiteList(array $stagesToCheck = [], array $allowedStages = []): bool
	{
		if (!empty($stagesToCheck) && !empty($allowedStages))
		{
			foreach ($stagesToCheck as $stage)
			{
				$allowed = false;
				foreach ($allowedStages as $allowedStage)
				{
					if ($allowedStage->id === (int)$stage)
					{
						$allowed = true;
						break;
					}
				}
				if (!$allowed)
				{
					return false;
				}
			}
			return true;
		}
		return false;
	}

	private function hasEditStagesPermission(array $stagesOrder = [])
	{
		if (\Bitrix\Tasks\Util\User::isSuper($this->userId))
		{
			return true;
		}
		$stages = $this->getStages();
		if (!$this->stagesInWhiteList($stagesOrder, $stages))
		{
			return false;
		}
		if (isset($this->projectId))
		{
			return $this->hasEditProjectStagesPermission();
		}
		return true;
	}

	private function hasEditProjectStagesPermission()
	{
		if (isset($this->projectId))
		{
			if (\Bitrix\Tasks\Util\User::isSuper($this->userId))
			{
				return true;
			}
			$role = $this->getUserRoleInProject();
			if ($role === SONET_ROLES_OWNER || $role === SONET_ROLES_MODERATOR)
			{
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * @throws \Bitrix\TasksMobile\Exception\ModuleNotFoundException
	 */
	public function getUserRoleInProject()
	{
		if (!Socialnetwork::includeModule())
		{
			throw new ModuleNotFoundException('SocialNetwork module not found');
		}

		return \CSocNetUserToGroup::GetUserRole($this->userId, $this->projectId);
	}

	/**
	 * @return Stage[]
	 * @throws \Bitrix\TasksMobile\Exception\ModuleNotFoundException
	 */
	private function getStages(): array
	{
		$stagesEntityId = $this->workMode === StagesTable::WORK_MODE_GROUP ? $this->projectId : $this->searchParams->ownerId;
		if (!Socialnetwork::includeModule())
		{
			throw new ModuleNotFoundException('SocialNetwork module not found');
		}

		$isScrumTask = false;
		if ($this->workMode === StagesTable::WORK_MODE_GROUP)
		{
			$group = WorkGroup::getById($stagesEntityId);
			$isScrumTask = $group && $group->isScrumProject();
		}

		$prevWorkMode = StagesTable::getWorkMode();
		StagesTable::setWorkMode($this->workMode);

		if ($isScrumTask && $this->taskId)
		{
			$kanbanService = new KanbanService();
			$stages = $kanbanService->getStagesToTask($this->taskId);
		}
		else
		{
			if ($this->workMode === StagesTable::WORK_MODE_GROUP
				&& !Group::canReadGroupTasks($this->userId, $stagesEntityId))
			{
				StagesTable::setWorkMode($prevWorkMode);

				return [];
			}

			$stages = $this->getStagesByEntityId($stagesEntityId);
		}

		if (!empty($stages))
		{
			$filter = $this->getFilter();
			$params = $this->getParams($stagesEntityId);
			$stages = $this->fillCountersData($stages, $filter, $params, $isScrumTask);
		}

		StagesTable::setWorkMode($prevWorkMode);

		return array_values($stages);
	}

	private function fillCountersData(array $stages, array $filter = [], array $params = [], ?bool $isScrumTask = false): array
	{
		$counts = [];
		$stagesWithCounters = [];
		$timeLineMode = $this->workMode === StagesTable::WORK_MODE_TIMELINE;
		if (!$timeLineMode && !$isScrumTask)
		{
			$counts = StagesTable::getStagesCount(
				$stages,
				$filter,
				$params['USER_ID'] ?? false,
			);
		}

		foreach ($stages as $index => $stage)
		{
			$isFirstStage = ($index === array_key_first($stages));
			$count = 0;

			if (!empty($stage['ADDITIONAL_FILTER']))
			{
				$filterTmp = array_merge(
					$filter,
					$stage['ADDITIONAL_FILTER']
				);
				$count = CTasks::GetCountInt($filterTmp);
			}
			else
			{
				$stageId = (int)($stage['ID'] ?? 0);
				$stagesEntityId = (int)($params['STAGES_ENTITY_ID'] ?? 0);
				if (
					$stageId !== 0
					&& $stagesEntityId !== 0
					&& !$isScrumTask
				)
				{
					$stages = (array)StagesTable::getStageIdByCode(
						$stageId,
						$stagesEntityId
					);
					foreach ($stages as $stId)
					{
						if (isset($counts[$stId]))
						{
							$count += $counts[$stId];
						}
					}
				}
			}

			$deadline = null;
			$leftBorder = null;
			$rightBorder = null;

			if ($timeLineMode)
			{
				if (!empty($stage['TO_UPDATE']['DEADLINE']))
				{
					$deadline = (new DateTime($stage['TO_UPDATE']['DEADLINE']))->getTimestamp();
				}
				if (!empty($stage['ADDITIONAL_FILTER']['>DEADLINE']))
				{
					$leftBorder = (new DateTime($stage['ADDITIONAL_FILTER']['>DEADLINE']))->getTimestamp();
				}
				if (!empty($stage['ADDITIONAL_FILTER']['<=DEADLINE']))
				{
					$rightBorder = (new DateTime($stage['ADDITIONAL_FILTER']['<=DEADLINE']))->getTimestamp();
				}
			}

			$stagesWithCounters[] = Stage::make([
				'id' => $stage['ID'],
				'aliasId' => $isFirstStage ? 0 : $stage['ID'],
				'name' => $stage['TITLE'],
				'color' => '#' . $stage['COLOR'],
				'statusId' => (string)$stage['SYSTEM_TYPE'],
				'sort' => $stage['SORT'],
				'counters' => [
					'total' => $count,
				],
				'entityType' => $stage['ENTITY_TYPE'],
				'deadline' => $deadline,
				'leftBorder' => $leftBorder,
				'rightBorder' => $rightBorder,
			]);
		}

		return $stagesWithCounters;
	}

	private function getStagesByEntityId(int $entityId): array
	{
		return StagesTable::getStages($entityId);
	}

	private function getFilter(): array
	{
		$taskProvider = new TaskProvider(
			$this->userId,
			TaskProvider::ORDER_ACTIVITY,
			$this->extra,
			$this->searchParams,
			null,
		);

		return $taskProvider->getFilter($this->workMode, null, $this->projectId);
	}

	private function getParams(int $stagesEntityId): array
	{
		return [
			'USER_ID' => $this->searchParams->ownerId,
			'STAGES_ENTITY_ID' => $stagesEntityId,
		];
	}

	private function verifyCanMoveStage(): bool
	{
		$entityId = $this->workMode === StagesTable::WORK_MODE_GROUP ? $this->projectId : $this->searchParams->ownerId;
		$entityId = (int)$entityId;

		if (!$entityId)
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		if (
			$this->workMode === StagesTable::WORK_MODE_GROUP
			&& !SocialNetwork\Group::can($entityId, SocialNetwork\Group::ACTION_SORT_TASKS)
		)
		{
			return false;
		}

		if (
			$this->workMode !== StagesTable::WORK_MODE_GROUP
			&& $entityId !== $this->userId
		)
		{
			return false;
		}

		return true;
	}

	public function getProjectTaskStageId(int $taskId, int $projectId): int
	{
		$taskItem = new \CTaskItem($taskId, $this->userId);
		if (!$taskItem->checkCanRead())
		{
			return 0;
		}

		$taskData = $taskItem->getData();
		$stageId = (int)($taskData['STAGE_ID'] ?? 0);
		if ($stageId === 0)
		{
			return StagesTable::getStageIdByCode(
				$stageId,
				$projectId
			);
		}

		return $stageId;
	}
}
