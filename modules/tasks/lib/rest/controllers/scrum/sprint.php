<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Rest\Controllers\Scrum;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\RobotService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Scrum\Service\TaskService;

class Sprint extends Base
{
	use UserTrait;

	/**
	 * Returns available fields.
	 *
	 * @return array
	 */
	public function getFieldsAction(): array
	{
		return [
			'fields' => [
				'groupId' => [
					'type' => 'integer',
				],
				'name' => [
					'type' => 'string',
				],
				'sort' => [
					'type' => 'integer',
				],
				'createdBy' => [
					'type' => 'integer',
				],
				'modifiedBy' => [
					'type' => 'integer',
				],
				'dateStart' => [
					'type' => 'string',
				],
				'dateEnd' => [
					'type' => 'string',
				],
				'status' => [
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * Returns sprint.
	 *
	 * @param int $id Sprint id.
	 * @return null
	 */
	public function getAction(int $id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return null;
		}

		$sprint = (new SprintService())->getSprintById($id);

		if ($sprint->isEmpty())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$sprint->getId() || !$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * Adds sprint.
	 *
	 * @param array $fields Sprint fields.
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		$groupId = array_key_exists('groupId', $fields) ? (int) $fields['groupId'] : 0;
		if (!$groupId)
		{
			$this->errorCollection->add([new Error('Group id not found')]);

			return null;
		}

		if (!$this->checkAccess($groupId))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$name = array_key_exists('name', $fields) ? (string) $fields['name'] : '';
		$sort = array_key_exists('sort', $fields) ? (int) $fields['sort'] : 0;

		$createdBy = 0;
		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}
		}
		$createdBy = $createdBy ?? $this->getUserId();

		$modifiedBy = 0;
		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}
		}
		$modifiedBy = $modifiedBy ?? $this->getUserId();

		$dateStart = array_key_exists('dateStart', $fields) ? $this->formatDateField($fields['dateStart']) : false;
		if ($dateStart === false)
		{
			$this->errorCollection->add([new Error('Incorrect dateStart format')]);

			return null;
		}

		$dateEnd = array_key_exists('dateEnd', $fields) ? $this->formatDateField($fields['dateEnd']) : false;
		if ($dateEnd === false)
		{
			$this->errorCollection->add([new Error('Incorrect dateEnd format')]);

			return null;
		}

		$status = array_key_exists('status', $fields) ? (string) $fields['status'] : '';

		$availableStatuses = [
			EntityForm::SPRINT_ACTIVE,
			EntityForm::SPRINT_PLANNED,
			EntityForm::SPRINT_COMPLETED,
		];
		if (!in_array($status, $availableStatuses, true))
		{
			$this->errorCollection->add([new Error('Incorrect sprint status')]);

			return null;
		}

		$sprint = new EntityForm();

		$sprint->setGroupId($groupId);
		$sprint->setName($name);
		$sprint->setSort($sort);
		$sprint->setCreatedBy($createdBy);
		$sprint->setModifiedBy($modifiedBy);
		$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
		$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
		$sprint->setStatus($status);

		$sprintService = new SprintService();

		$activeSprint = $sprintService->getActiveSprintByGroupId($groupId);
		if (!$activeSprint->isEmpty() && $sprint->isActiveSprint())
		{
			$this->errorCollection->add([new Error('Unable to add two active sprint')]);

			return null;
		}

		$sprint = $sprintService->createSprint($sprint);

		$sprint = $sprintService->getSprintById($sprint->getId());

		if (!empty($sprintService->getErrors()))
		{
			$this->errorCollection->add([new Error('Unable to add sprint')]);

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * Updates sprint.
	 *
	 * @param int $id Sprint id.
	 * @param array $fields Sprint fields.
	 * @return array|null
	 */
	public function updateAction(int $id, array $fields)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if ($sprint->getEntityType() !== EntityForm::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (array_key_exists('groupId', $fields) && is_numeric($fields['groupId']))
		{
			$groupId = (int) $fields['groupId'];
			$itemService = new ItemService();

			$isGroupUpdatingAction = ($sprint->getGroupId() !== $groupId);
			$hasSprintItems = (!empty($itemService->getItemIdsByEntityId($sprint->getId())));

			if ($isGroupUpdatingAction && $hasSprintItems)
			{
				$this->errorCollection->add([new Error('It is forbidden move a sprint with items')]);

				return null;
			}

			$sprint->setGroupId($groupId);
		}

		if (array_key_exists('name', $fields) && is_string($fields['name']))
		{
			$sprint->setName($fields['name']);
		}

		if (array_key_exists('sort', $fields) && is_numeric($fields['sort']))
		{
			$sprint->setSort((int) $fields['sort']);
		}

		if (array_key_exists('createdBy', $fields) && is_numeric($fields['createdBy']))
		{
			$createdBy = (int) $fields['createdBy'];
			if (!$this->existsUser($createdBy))
			{
				$this->errorCollection->add([new Error('createdBy user not found')]);

				return null;
			}

			$sprint->setCreatedBy($createdBy);
		}

		$modifiedBy = 0;
		if (array_key_exists('modifiedBy', $fields) && is_numeric($fields['modifiedBy']))
		{
			$modifiedBy = (int) $fields['modifiedBy'];
			if (!$this->existsUser($modifiedBy))
			{
				$this->errorCollection->add([new Error('modifiedBy user not found')]);

				return null;
			}
		}
		$sprint->setModifiedBy($modifiedBy ?? $this->getUserId());

		if (array_key_exists('dateStart', $fields))
		{
			$dateStart = $this->formatDateField($fields['dateStart']);

			if ($dateStart === false)
			{
				$this->errorCollection->add([new Error('Incorrect dateStart format')]);

				return null;
			}

			$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
		}

		if (array_key_exists('dateEnd', $fields))
		{
			$dateEnd = $this->formatDateField($fields['dateEnd']);

			if ($dateEnd === false)
			{
				$this->errorCollection->add([new Error('Incorrect dateEnd format')]);

				return null;
			}

			$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
		}

		$sprintService = new SprintService();

		if (array_key_exists('status', $fields) && is_string($fields['status']))
		{
			$availableStatuses = [
				EntityForm::SPRINT_ACTIVE,
				EntityForm::SPRINT_PLANNED,
				EntityForm::SPRINT_COMPLETED,
			];
			if (!in_array($fields['status'], $availableStatuses, true))
			{
				$this->errorCollection->add([new Error('Incorrect sprint status')]);

				return null;
			}

			$sprint->setStatus($fields['status']);

			$activeSprint = $sprintService->getActiveSprintByGroupId($sprint->getGroupId());
			if (!$activeSprint->isEmpty() && $sprint->isActiveSprint())
			{
				$this->errorCollection->add([new Error('Unable to add two active sprint')]);

				return null;
			}
		}

		if (!$sprintService->changeSprint($sprint))
		{
			$this->errorCollection->add([new Error('Sprint not updated')]);

			return null;
		}

		$sprint = $sprintService->getSprintById($sprint->getId());

		return $sprint->toArray();
	}

	/**
	 * Removes sprint.
	 *
	 * @param int $id Sprint id.
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteAction(int $id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}
		if ($sprint->getEntityType() !== EntityForm::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$itemService = new ItemService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($sprint->getGroupId());

		$hasSprintItems = (!empty($itemService->getItemIdsByEntityId($sprint->getId())));

		if ($hasSprintItems)
		{
			if ($backlog->isEmpty())
			{
				$this->errorCollection->add([new Error('It is forbidden remove a sprint with items')]);

				return null;
			}
			else
			{
				$itemService->moveItemsToEntity(
					$itemService->getItemIdsByEntityId($sprint->getId()),
					$backlog->getId()
				);

				if ($itemService->getErrors())
				{
					$this->errorCollection->add([new Error('Sprint items have not been moved to backlog')]);

					return null;
				}
			}
		}

		$sprintService = new SprintService();
		if (!$sprintService->removeSprint($sprint))
		{
			$this->errorCollection->add([new Error('Sprint not deleted')]);
		}

		return [];
	}

	/**
	 * Start sprint.
	 *
	 * @param int $id Sprint id.
	 * @return array|null
	 */
	public function startAction(int $id)
	{
		$id = (int) $id;
		if (!$id)
		{
			$this->errorCollection->add([new Error('Sprint id not found')]);

			return null;
		}

		$entityService = new EntityService();
		$sprint = $entityService->getEntityById($id);

		if (!$sprint->getId())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if ($sprint->getEntityType() !== EntityForm::SPRINT_TYPE)
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$sprint->isPlannedSprint())
		{
			$this->errorCollection->add([new Error('Sprint must be planned')]);

			return null;
		}

		$sprintService = new SprintService();

		if (!$sprintService->canStartSprint($this->getUserId(), $sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$kanbanService = new KanbanService();
		$taskService = new TaskService($this->getUserId());
		$itemService = new ItemService();
		$backlogService = new BacklogService();
		$robotService = (Loader::includeModule('bizproc') ? new RobotService() : null);

		$sprint = $sprintService->startSprint(
			$sprint,
			$taskService,
			$kanbanService,
			$itemService,
			$backlogService,
			$robotService
		);

		if (!empty($sprintService->getErrors()))
		{
			$this->errorCollection->add([new Error('Unable to start sprint')]);

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * Completes active sprint.
	 *
	 * @param int $id Group id.
	 * @return array|null
	 */
	public function completeAction(int $id)
	{
		$groupId = (int) $id;
		if (!$groupId)
		{
			$this->errorCollection->add([new Error('Group id not found')]);

			return null;
		}

		$sprintService = new SprintService();

		$sprint = $sprintService->getActiveSprintByGroupId($groupId);

		if ($sprint->isEmpty())
		{
			$this->errorCollection->add([new Error('Sprint not found')]);

			return null;
		}

		if (!$sprintService->canCompleteSprint($this->getUserId(), $sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		if (!$this->checkAccess($sprint->getGroupId()))
		{
			$this->errorCollection->add([new Error('Access denied')]);

			return null;
		}

		$entityService = new EntityService();
		$kanbanService = new KanbanService();
		$itemService = new ItemService();
		$taskService = new TaskService($this->getUserId());
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($sprint->getGroupId());

		$sprint = $sprintService->completeSprint(
			$sprint,
			$entityService,
			$taskService,
			$kanbanService,
			$itemService,
			$backlog->getId()
		);
		if ($sprintService->getErrors())
		{
			$this->errorCollection->add($this->getErrors());

			return null;
		}

		return $sprint->toArray();
	}

	/**
	 * Returns list sprints.
	 *
	 * @param PageNavigation $nav
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array
	 */
	public function listAction(
		$filter = [],
		$select = [],
		$order = [],
		PageNavigation $nav = null
	)
	{
		$filter['=ENTITY_TYPE'] = EntityForm::SPRINT_TYPE;

		$queryResult = (new EntityService($this->getUserId()))->getList($nav, $filter, $select, $order);

		if (!$queryResult)
		{
			$this->errorCollection->add([new Error('Could not load list')]);

			return [];
		}

		$sprints = [];

		$n = 0;
		while ($data = $queryResult->fetch())
		{
			$n++;
			if ($nav && $n > $nav->getPageSize())
			{
				break;
			}

			$sprint = new EntityForm();

			$sprint->fillFromDatabase($data);

			$sprints[] = $sprint->toArray();
		}

		if ($nav)
		{
			$nav->setRecordCount($nav->getOffset() + $n);
		}

		return $sprints;
	}

	private function formatDateField($inputDate): ?int
	{
		if (is_numeric($inputDate))
		{
			$date = (int) $inputDate;
		}
		else
		{
			$date = (is_string($inputDate) ? $inputDate : '');
			$date = strtotime($date);
		}

		return $date;
	}
}