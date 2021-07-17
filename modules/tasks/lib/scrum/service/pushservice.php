<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;

class PushService
{
	public function sendAddItemEvent(ItemTable $item): void
	{
		if ($item->getItemType() === ItemTable::EPIC_TYPE)
		{
			$this->sendAddEpicEvent($item);
			return;
		}

		$entityService = new EntityService();
		$entity = $entityService->getEntityById($item->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$itemService = new ItemService();
		$userService = new UserService();

		$taskService = null;
		if ($item->getSourceId())
		{
			$taskService = new TaskService($item->getCreatedBy());
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemAdded',
				'params' => $this->getItemData($item, $itemService, $taskService, $userService),
			]
		);
	}

	public function sendUpdateItemEvent(ItemTable $updatedItem): void
	{
		if ($updatedItem->getItemType() === ItemTable::EPIC_TYPE)
		{
			$this->sendUpdateEpicEvent($updatedItem);
			return;
		}

		$entityService = new EntityService();
		$entity = $entityService->getEntityById($updatedItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$itemService = new ItemService();
		$userService = new UserService();

		$item = $itemService->getItemById($updatedItem->getId());
		$item->setTmpId($updatedItem->getTmpId());

		$taskService = null;
		if ($item->getSourceId())
		{
			$taskService = new TaskService($item->getCreatedBy());
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemUpdated',
				'params' => $this->getItemData($item, $itemService, $taskService, $userService),
			]
		);
	}

	public function sendRemoveItemEvent(ItemTable $removedItem): void
	{
		if ($removedItem->getItemType() === ItemTable::EPIC_TYPE)
		{
			$this->sendRemoveEpicEvent($removedItem);
			return;
		}

		$entityService = new EntityService();
		$entity = $entityService->getEntityById($removedItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemRemoved',
				'params' => [
					'itemId' => $removedItem->getId(),
				],
			]
		);
	}

	public function sendAddEpicEvent(ItemTable $epic): void
	{
		$entityService = new EntityService();
		$entity = $entityService->getEntityById($epic->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicAdded',
				'params' => [
					'id' => $epic->getId(),
					'name' => $epic->getName(),
					'description' => $epic->getDescription(),
					'info' => $epic->getInfo()->getInfoData(),
				],
			]
		);
	}

	public function sendUpdateEpicEvent(ItemTable $epic): void
	{
		$entityService = new EntityService();
		$entity = $entityService->getEntityById($epic->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicUpdated',
				'params' => [
					'id' => $epic->getId(),
					'name' => $epic->getName(),
					'description' => $epic->getDescription(),
					'info' => $epic->getInfo()->getInfoData(),
				],
			]
		);
	}

	public function sendRemoveEpicEvent(ItemTable $epic): void
	{
		$entityService = new EntityService();
		$entity = $entityService->getEntityById($epic->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicRemoved',
				'params' => ['id' => $epic->getId()],
			]
		);
	}

	public function sendSortItemEvent(array $updatedItemsInfo): void
	{
		$itemService = new ItemService();

		reset($updatedItemsInfo);
		$itemId = key($updatedItemsInfo);

		$item = $itemService->getItemById($itemId);
		if ($item->isEmpty())
		{
			return;
		}

		$entityService = new EntityService();
		$entity = $entityService->getEntityById($item->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemSortUpdated',
				'params' => $updatedItemsInfo,
			]
		);
	}

	public function sendAddSprintEvent(EntityTable $inputSprint)
	{
		$sprintService = new SprintService();
		$sprint = $sprintService->getSprintById($inputSprint->getId());
		if ($sprintService->getErrors() || $sprint->isEmpty())
		{
			return;
		}

		$sprint->setTmpId($inputSprint->getTmpId());

		$tag = 'entityActions_' . $sprint->getGroupId();

		$sprintData = $sprintService->getSprintData($sprint);
		$sprintData['groupId'] = $sprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintAdded',
				'params' => $sprintData,
			]
		);
	}

	public function sendUpdateSprintEvent(EntityTable $inputSprint)
	{
		$sprintService = new SprintService();
		$sprint = $sprintService->getSprintById($inputSprint->getId());
		if ($sprintService->getErrors() || $sprint->isEmpty())
		{
			return;
		}

		$kanbanService = new KanbanService();
		$itemService = new ItemService();

		$sprintData = $sprintService->getSprintData($sprint);
		$sprintData['totalCompletedStoryPoints'] = $sprintService->getCompletedStoryPoints(
			$sprint,
			$kanbanService,
			$itemService
		);
		$sprintData['totalUncompletedStoryPoints'] = $sprintService->getUnCompletedStoryPoints(
			$sprint,
			$kanbanService,
			$itemService
		);
		$sprintData['completedTasks'] = count($kanbanService->getFinishedTaskIdsInSprint($sprint->getId()));
		$sprintData['uncompletedTasks'] = count($kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId()));
		$storyPoints = $sprintData['totalCompletedStoryPoints'] + $sprintData['totalUncompletedStoryPoints'];
		$sprintData['totalStoryPoints'] = ($sprint->isCompletedSprint() ? $storyPoints : $sprint->getStoryPoints());

		$tag = 'entityActions_' . $sprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintUpdated',
				'params' => $sprintData,
			]
		);
	}

	public function sendRemoveSprintEvent(EntityTable $inputSprint)
	{
		$tag = 'entityActions_' . $inputSprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintRemoved',
				'params' => [
					'sprintId' => $inputSprint->getId(),
				],
			]
		);
	}

	private function getItemData(
		ItemTable $item,
		ItemService $itemService,
		TaskService $taskService = null,
		UserService $userService = null
	): array
	{
		$itemData = $itemService->getItemData($item);

		$taskId = $item->getSourceId();
		if ($taskService && $taskId)
		{
			$itemData = $itemData + $taskService->getItemsData([$taskId])[$taskId];

			$entityService = new EntityService();

			$entity = $entityService->getEntityById($item->getEntityId());

			if ($entity->isActiveSprint())
			{
				if ($itemData['isParentTask'] === 'N' && !empty($itemData['completedSubTasksInfo']))
				{
					$itemData['isParentTask'] = 'Y';
				}

				if ($itemData['isParentTask'] === 'Y')
				{
					$kanbanService = new KanbanService();

					foreach ($itemData['completedSubTasksInfo'] as $sourceId => $subTaskInfo)
					{
						if ($kanbanService->isTaskInKanban($entity->getId(), $sourceId))
						{
							$itemData['subTasksInfo'][$sourceId] = $subTaskInfo;
						}
					}

					unset($itemData['completedSubTasksInfo']);

					$itemData['isParentTask'] = ($itemData['subTasksInfo'] ? 'Y' : 'N');
					$itemData['subTasksCount'] = count($itemData['subTasksInfo']);
					$itemData['subTasksInfo'] = $this->getSubStoryPoints($itemData['subTasksInfo'], $itemService);
				}
			}
			else if ($entity->isCompletedSprint())
			{
				if ($itemData['isSubTask'] === 'Y')
				{
					$itemData['isSubTask'] = 'N';
				}
			}
			else
			{
				if ($itemData['isParentTask'] === 'Y')
				{
					$itemData['subTasksInfo'] = $this->getSubStoryPoints($itemData['subTasksInfo'], $itemService);
				}
			}
		}

		if ($userService && isset($itemData['responsibleId']))
		{
			$itemData['responsible'] = $userService->getInfoAboutUsers([$itemData['responsibleId']]);
		}

		return $itemData;
	}

	private function getSubStoryPoints(array $subTasksInfo, ItemService $itemService): array
	{
		foreach ($subTasksInfo as $sourceId => $subTaskInfo)
		{
			$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId([$sourceId]);
			$subTasksInfo[$sourceId]['storyPoints'] = $itemsStoryPoints[$sourceId];
		}

		return $subTasksInfo;
	}
}