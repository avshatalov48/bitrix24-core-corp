<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Form\ItemForm;

class PushService
{
	public function sendAddItemEvent(ItemForm $addedItem): void
	{
		$entityService = new EntityService();

		$entity = $entityService->getEntityById($addedItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemAdded',
				'params' => [
					'id' => $addedItem->getId(),
					'groupId' => $entity->getGroupId(),
				],
			]
		);
	}

	public function sendUpdateItemEvent(ItemForm $updatedItem): void
	{
		$entityService = new EntityService();

		$entity = $entityService->getEntityById($updatedItem->getEntityId());
		if ($entityService->getErrors() || $entity->isEmpty())
		{
			return;
		}

		$tag = 'itemActions_' . $entity->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'itemUpdated',
				'params' => [
					'id' => $updatedItem->getId(),
					'groupId' => $entity->getGroupId(),
					'sourceId' => $updatedItem->getSourceId(),
					'tmpId' => $updatedItem->getTmpId(),
				],
			]
		);
	}

	public function sendRemoveItemEvent(ItemForm $removedItem): void
	{
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
					'id' => $removedItem->getId(),
					'groupId' => $entity->getGroupId(),
				],
			]
		);
	}

	public function sendAddEpicEvent(EpicForm $epic): void
	{
		$tag = 'itemActions_' . $epic->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicAdded',
				'params' => $epic->toArray(),
			]
		);
	}

	public function sendUpdateEpicEvent(EpicForm $epic): void
	{
		$tag = 'itemActions_' . $epic->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicUpdated',
				'params' => $epic->toArray(),
			]
		);
	}

	public function sendRemoveEpicEvent(EpicForm $epic): void
	{
		$tag = 'itemActions_' . $epic->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'epicRemoved',
				'params' => $epic->toArray(),
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

	public function sendAddSprintEvent(EntityForm $inputSprint)
	{
		$tag = 'entityActions_' . $inputSprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintAdded',
				'params' => [
					'id' => $inputSprint->getId(),
					'groupId' => $inputSprint->getGroupId(),
					'tmpId' => $inputSprint->getTmpId(),
				],
			]
		);
	}

	public function sendUpdateSprintEvent(EntityForm $inputSprint)
	{
		$tag = 'entityActions_' . $inputSprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintUpdated',
				'params' => [
					'id' => $inputSprint->getId(),
					'groupId' => $inputSprint->getGroupId(),
				],
			]
		);
	}

	public function sendRemoveSprintEvent(EntityForm $inputSprint)
	{
		$tag = 'entityActions_' . $inputSprint->getGroupId();

		\CPullWatch::addToStack(
			$tag,
			[
				'module_id' => 'tasks',
				'command' => 'sprintRemoved',
				'params' => [
					'id' => $inputSprint->getId(),
					'groupId' => $inputSprint->getGroupId(),
				],
			]
		);
	}
}
