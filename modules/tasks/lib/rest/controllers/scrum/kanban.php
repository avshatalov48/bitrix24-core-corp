<?php

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Error;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;

class Kanban extends Base
{
	use UserTrait;

	/**
	 * Returns available stage fields.
	 *
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'name' => [
					'type' => 'string',
				],
				'sort' => [
					'type' => 'integer',
				],
				'type' => [
					'type' => 'string',
				],
				'sprintId' => [
					'type' => 'integer',
				],
				'color' => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * Adds Scrum kanban stage.
	 *
	 * @param array $fields Stage fields.
	 * @return int|null
	 */
	public function addStageAction(array $fields): ?int
	{
		$sprintId = array_key_exists('sprintId', $fields) ? (int) $fields['sprintId'] : 0;
		if (!$sprintId)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return null;
		}

		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($sprintId);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (!array_key_exists('name', $fields) || !is_string($fields['name']))
		{
			$this->errorCollection->add([new Error('Incorrect name format')]);

			return null;
		}

		$stage = [
			'TITLE' => $fields['name'],
			'ENTITY_ID' => $sprint->getId(),
			'ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
		];

		$availableTypes = [
			StagesTable::SYS_TYPE_NEW,
			StagesTable::SYS_TYPE_PROGRESS,
			StagesTable::SYS_TYPE_FINISH,
		];
		$type = StagesTable::SYS_TYPE_PROGRESS;
		if (array_key_exists('type', $fields) && in_array($fields['type'], $availableTypes, true))
		{
			$type = $fields['type'];
		}
		$stage['SYSTEM_TYPE'] = $type;

		$stage['SORT'] = is_numeric($fields['sort'] ?? null) ? (int) $fields['sort'] : 100;
		$stage['COLOR'] = array_key_exists('color', $fields) ? (string) $fields['color'] : '00C4FB';

		try
		{
			$result = StagesTable::add($stage);
			if ($result->isSuccess())
			{
				return $result->getId();
			}
			else
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error('System error'));

			return null;
		}
	}

	/**
	 * Returns Scrum kanban stages.
	 *
	 * @param int $sprintId
	 * @return array|null
	 */
	public function getStagesAction(int $sprintId): ?array
	{
		$sprintId = (int) $sprintId;
		if (!$sprintId)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return null;
		}

		$entityService = new EntityService();
		$kanbanService = new KanbanService();

		$sprint = $entityService->getEntityById($sprintId);
		if (!$sprint->getId() || $sprint->getEntityType() !== EntityForm::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		$stages = [];

		foreach ($kanbanService->getStages($sprintId) as $stage)
		{
			$stages[] = [
				'id' => $stage['ID'],
				'name' => $stage['TITLE'],
				'sort' => $stage['SORT'],
				'type' => $stage['SYSTEM_TYPE'],
				'sprintId' => $stage['ENTITY_ID'],
				'color' => $stage['COLOR'],
			];
		}

		return $stages;
	}

	/**
	 * Updates stage.
	 *
	 * @param int $stageId Stage id.
	 * @param array $fields Stage fields.
	 * @return bool
	 */
	public function updateStageAction(int $stageId, array $fields): bool
	{
		$stageId = (int) $stageId;
		if (!$stageId)
		{
			$this->errorCollection->add([new Error('Stage id not found')]);

			return false;
		}

		try
		{
			$queryObject = StagesTable::getById($stageId);
			if ($stage = $queryObject->fetch())
			{
				$sprintId = $stage['ENTITY_ID'];

				$sprintService = new SprintService();

				$sprint = $sprintService->getSprintById($sprintId);

				if (!$sprint->getId() || !$this->checkAccess($sprint->getGroupId()))
				{
					$this->errorCollection->add([new Error('Access denied')]);

					return false;
				}

				$updatedFields = [];

				if (array_key_exists('name', $fields) && is_string($fields['name']))
				{
					$updatedFields['TITLE'] = $fields['name'];
				}

				if (array_key_exists('sprintId', $fields) && is_numeric($fields['sprintId']))
				{
					$newSprint = $sprintService->getSprintById($fields['sprintId']);
					if (!$newSprint->getId() || !$this->checkAccess($newSprint->getGroupId()))
					{
						$this->errorCollection->add([new Error('Incorrect sprintId value')]);

						return false;
					}

					$updatedFields['ENTITY_ID'] = $newSprint->getId();
				}

				if (array_key_exists('color', $fields) && is_string($fields['color']))
				{
					$updatedFields['COLOR'] = $fields['color'];
				}

				if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
				{
					$updatedFields['SORT'] = $fields['sort'];
				}

				$availableTypes = [
					StagesTable::SYS_TYPE_NEW,
					StagesTable::SYS_TYPE_PROGRESS,
					StagesTable::SYS_TYPE_FINISH,
				];
				if (array_key_exists('type', $fields) && in_array($fields['type'], $availableTypes, true))
				{
					$updatedFields['SYSTEM_TYPE'] = $fields['type'];
				}

				if ($updatedFields)
				{
					$result = StagesTable::update($stage['ID'], $updatedFields);

					if (!$result->isSuccess())
					{
						$this->errorCollection->setError(new Error('System error'));

						return false;
					}
				}

				return true;
			}
			else
			{
				$this->errorCollection->setError(new Error('Stage not found'));

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error('System error'));

			return false;
		}
	}

	/**
	 * Removes stage.
	 *
	 * @param int $stageId Stage id.
	 * @return bool
	 */
	public function deleteStageAction(int $stageId): bool
	{
		$stageId = (int) $stageId;
		if (!$stageId)
		{
			$this->errorCollection->add([new Error('Stage id not found')]);

			return false;
		}

		try
		{
			$queryObject = StagesTable::getById($stageId);
			if ($stage = $queryObject->fetch())
			{
				$sprintId = $stage['ENTITY_ID'];

				$sprintService = new SprintService();

				$sprint = $sprintService->getSprintById($sprintId);

				if (!$sprint->getId() || !$this->checkAccess($sprint->getGroupId()))
				{
					$this->errorCollection->add([new Error('Access denied')]);

					return false;
				}

				$queryObject = TaskStageTable::getList([
					'select' => ['TASK_ID'],
					'filter' => [
						'STAGE_ID' => $stage['ID'],
						'=STAGE.ENTITY_ID' => $sprint->getId(),
						'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_ACTIVE_SPRINT,
					]
				]);
				if ($queryObject->fetch())
				{
					$this->errorCollection->add([new Error('Stage has tasks')]);

					return false;
				}
				else
				{
					if ($stage['SYSTEM_TYPE'] == StagesTable::SYS_TYPE_NEW)
					{
						$result = StagesTable::update($stage['ID'], ['SYSTEM_TYPE' => '']);
						if (!$result->isSuccess())
						{
							$this->errorCollection->setError(new Error('System error'));

							return false;
						}
					}

					StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

					$res = StagesTable::delete($stage['ID'], $sprint->getId());
					if ($res && $res->isSuccess())
					{
						return true;
					}
					else
					{
						$this->errorCollection->setError(new Error('System error'));

						return false;
					}
				}
			}
			else
			{
				$this->errorCollection->setError(new Error('Stage not found'));

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error('System error'));

			return false;
		}
	}

	/**
	 * Adds task to kanban.
	 *
	 * @param int $sprintId Sprint id.
	 * @param int $taskId Task id.
	 * @param int $stageId Stage id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \TasksException
	 */
	public function addTaskAction(int $sprintId, int $taskId, int $stageId): bool
	{
		$sprintId = (int) $sprintId;
		if (!$sprintId)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return false;
		}

		$taskId = (int) $taskId;
		if (!$taskId)
		{
			$this->errorCollection->add([new Error('Task id not found')]);

			return false;
		}

		$stageId = (int) $stageId;
		if (!$stageId)
		{
			$this->errorCollection->add([new Error('Stage id not found')]);

			return false;
		}

		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($sprintId);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return false;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return false;
		}

		$queryObject = StagesTable::getById($stageId);
		if (!$queryObject->fetch())
		{
			$this->errorCollection->add([new Error('Stage not found')]);

			return false;
		}

		$queryObject = \CTasks::getList(
			['ID' => 'ASC'],
			[
				'ID' => $taskId,
				'GROUP_ID' => $sprint->getGroupId(),
				'CHECK_PERMISSIONS' => 'N',
			],
			['ID']
		);
		if (!$queryObject->fetch())
		{
			$this->errorCollection->add([new Error('Task not found. The task must be with GROUP_ID')]);

			return false;
		}

		$result = TaskStageTable::add([
			'TASK_ID' => $taskId,
			'STAGE_ID' => $stageId,
		]);

		if ($result->isSuccess())
		{
			return $result->getId();
		}
		else
		{
			$this->errorCollection->setError(new Error('System error'));

			return false;
		}
	}

	/**
	 * Removes task from kanban.
	 *
	 * @param int $sprintId Sprint id.
	 * @param int $taskId Task id.
	 * @return bool
	 * @throws \TasksException
	 */
	public function deleteTaskAction(int $sprintId, int $taskId): bool
	{
		$sprintId = (int) $sprintId;
		if (!$sprintId)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return false;
		}

		$taskId = (int) $taskId;
		if (!$taskId)
		{
			$this->errorCollection->add([new Error('Task id not found')]);

			return false;
		}

		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($sprintId);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return false;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return false;
		}

		$queryObject = \CTasks::getList(
			['ID' => 'ASC'],
			[
				'ID' => $taskId,
				'GROUP_ID' => $sprint->getGroupId(),
				'CHECK_PERMISSIONS' => 'N',
			],
			['ID']
		);
		if (!$queryObject->fetch())
		{
			$this->errorCollection->add([new Error('Task not found. The task must be with GROUP_ID')]);

			return false;
		}

		$kanbanService = new KanbanService();

		$kanbanService->removeTasksFromKanban($sprint->getId(), [$taskId]);
		if ($kanbanService->getErrors())
		{
			$this->errorCollection->add($this->getErrors());

			return false;
		}

		return true;
	}
}