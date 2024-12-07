<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Form\TypeForm;
use Bitrix\Tasks\Util\Result;

class DefinitionOfDoneService implements Errorable
{
	const ERROR_COULD_NOT_ADD_DEFAULT_LIST_1 = 'TASKS_DOD_01';
	const ERROR_COULD_NOT_ADD_DEFAULT_LIST_2 = 'TASKS_DOD_02';

	private $executiveUserId;
	private $errorCollection;

	public function __construct(int $executiveUserId = 0)
	{
		$this->executiveUserId = $executiveUserId;

		$this->errorCollection = new ErrorCollection;
	}

	public static function existsDod(int $groupId): bool
	{
		$types = (new DefinitionOfDoneService())->getTypes($groupId);

		return (!empty($types));
	}

	public function mergeList(string $facade, int $entityId, array $items): Result
	{
		$result = new Result();

		foreach ($items as $id => $item)
		{
			$item['ID'] = ((int)($item['ID'] ?? null) === 0 ? null : (int)$item['ID']);

			$item['IS_COMPLETE'] = (
				($item['IS_COMPLETE'] === true)
				|| ((int)$item['IS_COMPLETE'] > 0)
				|| ($item['IS_COMPLETE'] === "true")
			);
			$item['IS_IMPORTANT'] = (
				($item['IS_IMPORTANT'] === true)
				|| ((int)$item['IS_IMPORTANT'] > 0)
				|| ($item['IS_IMPORTANT'] === "true")
			);

			$items[$item['NODE_ID']] = $item;
			unset($items[$id]);
		}

		return $facade::merge($entityId, $this->executiveUserId, $items, []);
	}

	public function removeList(string $facade, int $entityId): void
	{
		$facade::$currentAccessAction = CheckListFacade::ACTION_REMOVE;
		$facade::deleteByEntityId($entityId, $this->executiveUserId);
	}

	public function getComponent(int $entityId, string $entityType, ?array $items): Component
	{
		$randomGenerator = new RandomSequence(rand());

		return new Component(
			'bitrix:tasks.widget.checklist.new',
			'',
			[
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE' => $entityType,
				'DATA' => $items,
				'CONVERTED' => true,
				'CAN_ADD_ACCOMPLICE' => false,
				'SIGNATURE_SEED' => $randomGenerator->randString(6),
				'SHOW_COMPLETE_ALL_BUTTON' => $entityType === 'SCRUM_ITEM',
				'COLLAPSE_ON_COMPLETE_ALL' => false,
			]
		);
	}

	public function getTypeItems(int $entityId): array
	{
		$items = TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId);
		foreach (array_keys($items) as $id)
		{
			$items[$id]['COPIED_ID'] = $id;
			unset($items[$id]['ID']);
		}

		return $items;
	}

	public function isTypeListEmpty(int $entityId): bool
	{
		return empty(TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId));
	}

	public function createDefaultList(int $entityId): void
	{
		try
		{
			$result = TypeChecklistFacade::add($entityId, $this->executiveUserId, [
				'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_NEW_0'),
				'IS_COMPLETE' => 'N',
				'PARENT_ID' => 0,
			]);
			$newItem = $result->getData()['ITEM'];
			$newItemId = $newItem->getFields()['ID'];
			for ($i = 1; $i <= 3; $i++)
			{
				TypeChecklistFacade::add($entityId, $this->executiveUserId, [
					'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_NEW_' . $i),
					'IS_COMPLETE' => 'N',
					'PARENT_ID' => $newItemId,
				]);
			}
		}
		catch (\Exception $exception)
		{
			try
			{
				TypeChecklistFacade::deleteByEntityId($entityId, $this->executiveUserId);
			}
			catch (\Exception $exception)
			{
				$this->errorCollection->setError(
					new Error(
						$exception->getMessage(),
						self::ERROR_COULD_NOT_ADD_DEFAULT_LIST_1
					)
				);
			}

			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_DEFAULT_LIST_2
				)
			);
		}
	}

	public function isNecessary(int $groupId, int $taskId): bool
	{
		$taskService = new TaskService($this->executiveUserId);

		$task = current($taskService->getTasksInfo([$taskId]));
		if (!$task)
		{
			return false;
		}

		$parentId = (int)$task['PARENT_ID'];

		if ($parentId)
		{
			$queryObject = TaskTable::getList([
				'filter' => [
					'ID' => $parentId,
					'GROUP_ID' => $groupId,
				],
				'select' => ['ID'],
			]);
			if ($queryObject->fetch())
			{
				return false;
			}
		}

		if (self::existsDod($groupId))
		{
			return true;
		}

		return false;
	}

	public function getTypes(int $groupId): array
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$types = [];
		foreach ($typeService->getTypes($backlog->getId()) as $type)
		{
			$types[] = $type->toArray();
		}

		return $types;
	}

	public function getItemType(int $taskId): TypeForm
	{
		$itemService = new ItemService();
		$typeService = new TypeService();

		$item = $itemService->getItemBySourceId($taskId);

		return $typeService->getType($item->getTypeId());
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}
