<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\CheckList\Permission;

use Attribute;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\CheckList\Node\Nodes;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\CheckListMapper;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Save implements AttributeAccessInterface
{
	public function check(Entity\EntityInterface $entity, Context $context, array $parameters = []): bool
	{
		$checkListMapper = new CheckListMapper();
		$taskRepository = Container::getInstance()->getTaskRepository();

		if (!$entity->getId())
		{
			return false;
		}

		if ($entity->checklist === null)
		{
			return false;
		}

		$nodes = $checkListMapper->mapToNodes($entity->checklist);

		if (!TaskAccessController::can(
			userId: $context->getUserId(),
			action: ActionDictionary::ACTION_CHECKLIST_SAVE,
			itemId: $entity->getId(),
			params: $nodes->toArray(),
		))
		{
			return false;
		}

		$task = $taskRepository->getById($entity->getId());

		if (!$task)
		{
			return false;
		}

		if (!$this->canAssignAccomplices($nodes, $task, $context->getUserId()))
		{
			return false;
		}

		if (!$this->canAssignAuditors($nodes, $task, $context->getUserId()))
		{
			return false;
		}

		return true;
	}

	private function canAssignAccomplices(Nodes $nodes, Entity\Task $task, int $userId): bool
	{
		$checklistAccomplices = $nodes->getAccomplices();

		if (empty($checklistAccomplices))
		{
			return true;
		}

		$newAccomplices = array_diff($checklistAccomplices, (array)$task->accomplices?->getIdList());

		if (empty($newAccomplices))
		{
			return true;
		}

		$accompliceTask = TaskModel::createFromArray(['ID' => $task->getId(), 'ACCOMPLICES' => $newAccomplices]);

		if (!TaskAccessController::can(
			userId: $userId,
			action: ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES,
			itemId: $task->getId(),
			params: $accompliceTask,
		))
		{
			return false;
		}

		return true;
	}

	private function canAssignAuditors(Nodes $nodes, Entity\Task $task, int $userId): bool
	{
		$checklistAuditors = $nodes->getAuditors();

		if (empty($checklistAuditors))
		{
			return true;
		}

		$newAuditors = array_diff($checklistAuditors, (array)$task->auditors?->getIdList());

		if (empty($newAuditors))
		{
			return true;
		}

		if (!TaskAccessController::can(
			userId: $userId,
			action: ActionDictionary::ACTION_TASK_ADD_AUDITORS,
			itemId: $task->getId(),
			params: $newAuditors,
		))
		{
			return false;
		}

		return true;
	}
}
