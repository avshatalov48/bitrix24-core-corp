<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Tasks\Scrum\Internal\EntityTable;

class SprintService implements Errorable
{
	const ERROR_COULD_NOT_ADD_SPRINT = 'TASKS_SS_01';
	const ERROR_COULD_NOT_UPDATE_SPRINT = 'TASKS_SS_02';
	const ERROR_COULD_NOT_READ_SPRINT = 'TASKS_SS_03';
	const ERROR_COULD_NOT_REMOVE_SPRINT = 'TASKS_SS_04';
	const ERROR_COULD_NOT_START_SPRINT = 'TASKS_SS_05';
	const ERROR_COULD_NOT_COMPLETE_SPRINT = 'TASKS_SS_06';
	const ERROR_COULD_NOT_READ_ACTIVE_SPRINT = 'TASKS_SS_07';
	const ERROR_COULD_NOT_DETECT_ACTIVE_SPRINT = 'TASKS_SS_08';
	const ERROR_COULD_NOT_CHANGE_SORT = 'TASKS_SS_09';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createSprint(EntityTable $sprint): EntityTable
	{
		try
		{
			$result = EntityTable::add($sprint->getFieldsToCreateSprint());

			if ($result->isSuccess())
			{
				$sprint->setId($result->getId());
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_SPRINT);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_SPRINT));
		}

		return $sprint;
	}

	public function changeSprint(EntityTable $sprint): bool
	{
		try
		{
			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_UPDATE_SPRINT);
				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_UPDATE_SPRINT));
			return false;
		}
	}

	public function startSprint(EntityTable $sprint, KanbanService $kanbanService): EntityTable
	{
		try
		{
			$sprint->setStatus(EntityTable::SPRINT_ACTIVE);
			$sprint->setSort(0);

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if ($result->isSuccess())
			{
				$kanbanService->addTasksToKanban($sprint->getId(), $sprint->getTaskIds());
				if ($kanbanService->getErrors())
				{
					$this->errorCollection->add($kanbanService->getErrors());
				}
			}
			else
			{
				$this->errorCollection->setError(new Error(
					implode('; ', $result->getErrorMessages()),
					self::ERROR_COULD_NOT_START_SPRINT
				));
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_START_SPRINT));
		}

		return $sprint;
	}

	public function completeSprint(EntityTable $sprint): EntityTable
	{
		try
		{
			$sprint->setStatus(EntityTable::SPRINT_COMPLETED);
			$sprint->setInfo(array_merge($this->getCurrentInfo($sprint), [$sprint->getInfo()]));
			$sprint->setSort(0);

			$result = EntityTable::update($sprint->getId(), $sprint->getFieldsToUpdateEntity());

			if (!$result->isSuccess())
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_COMPLETE_SPRINT);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_COMPLETE_SPRINT)
			);
		}

		return $sprint;
	}

	/**
	 * Gets active sprint by group id.
	 *
	 * @param int $groupId Group id.
	 * @param ItemService $itemService Item Service.
	 * @return EntityTable
	 */
	public function getActiveSprintByGroupId(int $groupId, ItemService $itemService = null): EntityTable
	{
		$sprint = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID' => (int) $groupId,
					'STATUS' => EntityTable::SPRINT_ACTIVE
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);
				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ACTIVE_SPRINT)
			);
		}

		return $sprint;
	}

	public function isActiveSprint(EntityTable $sprint): bool
	{
		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> $sprint->getGroupId(),
					'STATUS' => EntityTable::SPRINT_ACTIVE
				]
			]);
			return (bool) $queryObject->fetch();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_DETECT_ACTIVE_SPRINT)
			);
			return false;
		}
	}

	/**
	 * Returns an array objects with sprints by scrum group id.
	 *
	 * @param int $groupId Scrum group id.
	 * @param ItemService $itemService Item service object.
	 * @return EntityTable []
	 */
	public function getSprintsByGroupId(int $groupId, ItemService $itemService = null): array
	{
		$sprints = [];

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'GROUP_ID'=> (int) $groupId,
					'ENTITY_TYPE' => EntityTable::SPRINT_TYPE
				],
				'order' => ['SORT' => 'ASC', 'DATE_END' => 'DESC']
			]);
			while ($sprintData = $queryObject->fetch())
			{
				$sprint = EntityTable::createEntityObject();

				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);

				if ($itemService)
				{
					$sprint->setChildren($itemService->getHierarchyChildItems($sprint));
				}

				$sprints[] = $sprint;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprints;
	}

	public function getSprintById(int $sprintId): EntityTable
	{
		$sprint = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => (int) $sprintId
				],
			]);
			if ($sprintData = $queryObject->fetch())
			{
				$sprint = $this->fillSprintObjectByTableData($sprint, $sprintData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_SPRINT));
		}

		return $sprint;
	}

	public function removeSprint(EntityTable $sprint): bool
	{
		try
		{
			$result = EntityTable::delete($sprint->getId());
			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE_SPRINT);
				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_SPRINT));
			return false;
		}
	}

	public function changeSort(array $sortInfo): void
	{
		try
		{
			$sprintIds = [];
			$whens = [];

			foreach ($sortInfo as $sprintId => $info)
			{
				$sprintId = (is_numeric($sprintId) ? (int) $sprintId : 0);
				if ($sprintId)
				{
					$sprintIds[] = $sprintId;
					$whens[] = 'WHEN ID = '.$sprintId.' THEN '.$info['sort'];
				}
			}

			if ($sprintIds)
			{
				$expression = new SqlExpression('(CASE '.implode(' ', $whens).' END)');

				EntityTable::updateMulti($sprintIds, [
					'SORT' => $expression
				]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHANGE_SORT));
		}
	}

	public function getCompletedStoryPoints(
		EntityTable $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	public function getUnCompletedStoryPoints(
		EntityTable $sprint,
		KanbanService $kanbanService,
		ItemService $itemService
	): float
	{
		$finishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
		return $itemService->getSumStoryPointsBySourceIds($finishedTaskIds);
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function fillSprintObjectByTableData(EntityTable $sprint, array $sprintData): EntityTable
	{
		$sprint->setId($sprintData['ID']);
		$sprint->setGroupId($sprintData['GROUP_ID']);
		$sprint->setEntityType($sprintData['ENTITY_TYPE']);
		$sprint->setName($sprintData['NAME']);
		if ($sprintData['SORT'])
		{
			$sprint->setSort($sprintData['SORT']);
		}
		$sprint->setCreatedBy($sprintData['CREATED_BY']);
		$sprint->setModifiedBy($sprintData['MODIFIED_BY']);
		if ($sprintData['DATE_START'])
		{
			$sprint->setDateStart($sprintData['DATE_START']);
		}
		if ($sprintData['DATE_END'])
		{
			$sprint->setDateEnd($sprintData['DATE_END']);
		}
		$sprint->setStatus($sprintData['STATUS']);
		if ($sprintData['INFO'])
		{
			$sprint->setInfo($sprintData['INFO']);
		}
		return $sprint;
	}

	private function getCurrentInfo(EntityTable $sprint): array
	{
		try
		{
			$queryObject = EntityTable::getList([
				'select' => ['INFO'],
				'filter' => [
					'ID'=> $sprint->getId(),
					'ENTITY_TYPE' => EntityTable::SPRINT_TYPE
				],
				'order' => ['SORT' => 'ASC']
			]);
			if ($sprintData = $queryObject->fetch())
			{
				return [];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
		}

		return [];
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}
}