<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;

class DefinitionOfDoneService extends Controller
{
	const ERROR_COULD_NOT_MERGE_LIST = 'TASKS_DOD_01';
	const ERROR_COULD_NOT_GET_DATA = 'TASKS_DOD_02';
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_DOD_03';
	const ERROR_COULD_NOT_ADD_DEFAULT_LIST = 'TASKS_DOD_04';
	const ERROR_COULD_NOT_SAVE_ITEM_LIST = 'TASKS_DOD_05';
	const ERROR_COULD_NOT_READ_DOD_SETTINGS = 'TASKS_DOD_06';
	const ERROR_COULD_NOT_REMOVE_LIST = 'TASKS_DOD_07';

	private $executiveUserId;

	public function __construct(int $executiveUserId = 0, Request $request = null)
	{
		parent::__construct($request);

		$this->executiveUserId = $executiveUserId;
	}

	public function mergeList(string $facade, int $entityId, array $items): Result
	{
		$result = new Result();

		try
		{
			foreach ($items as $id => $item)
			{
				$item['ID'] = ((int) $item['ID'] === 0 ? null : (int) $item['ID']);
				$item['IS_COMPLETE'] = ($item['IS_COMPLETE'] === "true");
				$item['IS_IMPORTANT'] = ($item['IS_IMPORTANT'] === "true");

				$items[$item['NODE_ID']] = $item;
				unset($items[$id]);
			}

			$result = $facade::merge($entityId, $this->executiveUserId, $items, []);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_MERGE_LIST));
		}

		return $result;
	}

	public function removeList(string $facade, int $entityId): void
	{
		try
		{
			$facade::$currentAccessAction = CheckListFacade::ACTION_REMOVE;
			$facade::deleteByEntityId($entityId, $this->executiveUserId);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_LIST
				)
			);
		}
	}

	public function getComponent(int $entityId, string $entityType, array $items): Component
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
		$items = [];

		try
		{
			$items = TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId);
			foreach (array_keys($items) as $id)
			{
				$items[$id]['COPIED_ID'] = $id;
				unset($items[$id]['ID']);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_DATA));
		}

		return $items;
	}

	public function isTypeListEmpty(int $entityId): bool
	{
		try
		{
			return empty(TypeChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId));
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_IS_EMPTY)
			);

			return false;
		}
	}

	public function createDefaultList(int $entityId): void
	{
		try
		{
			$result = TypeChecklistFacade::add($entityId, $this->executiveUserId, [
				'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_0'),
				'IS_COMPLETE' => 'N',
				'PARENT_ID' => 0
			]);
			$newItem = $result->getData()['ITEM'];
			$newItemId = $newItem->getFields()['ID'];
			for ($i = 1; $i <= 10; $i++)
			{
				TypeChecklistFacade::add($entityId, $this->executiveUserId, [
					'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_'.$i),
					'IS_COMPLETE' => 'N',
					'PARENT_ID' => $newItemId
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
					new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_DEFAULT_LIST)
				);
			}

			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_DEFAULT_LIST)
			);
		}
	}

	public function getSettingsAction(): ?array
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);

			$typeService = new TypeService();
			$backlogService = new BacklogService();
			$itemService = new ItemService();

			$backlog = $backlogService->getBacklogByGroupId($groupId);

			$types = [];
			foreach ($typeService->getTypes($backlog->getId()) as $type)
			{
				$types[] = $typeService->getTypeData($type);
			}

			$item = $itemService->getItemBySourceId($taskId);
			$activeTypeId = $item->getTypeId();

			return [
				'types' => $types,
				'activeTypeId' => $activeTypeId,
			];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ_DOD_SETTINGS
				)
			);
		}

		return null;
	}

	public function getListAction(): ?Component
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);

			$backlogService = new BacklogService();
			$itemService = new ItemService();

			$backlog = $backlogService->getBacklogByGroupId($groupId);
			$item = $itemService->getItemBySourceId($taskId);

			if ($item->isEmpty() || $backlogService->getErrors() || $backlog->isEmpty())
			{
				$this->errorCollection->setError(
					new Error(
						'System error',
						self::ERROR_COULD_NOT_GET_DATA
					)
				);

				return null;
			}

			if ($this->isItemListEmpty($item->getId()) || $item->getTypeId() !== $typeId)
			{
				$typeItems = $this->getTypeItems($typeId);

				$items = $this->convertTypeItems($item->getId(), $typeItems);
			}
			else
			{
				$items = $this->getItemItems($item->getId());
			}

			return $this->getComponent($item->getId(), 'SCRUM_ITEM', $items);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return null;
		}
	}

	public function saveListAction()
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);

			$this->executiveUserId = User::getId();

			$itemService = new ItemService();

			$scrumItem = $itemService->getItemBySourceId($taskId);

			if ($scrumItem->isEmpty())
			{
				$this->errorCollection->setError(
					new Error(
						Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_SAVE_ITEM_ERROR'),
						self::ERROR_COULD_NOT_SAVE_ITEM_LIST
					)
				);
				return null;
			}

			$scrumItem->setTypeId($typeId);

			$itemService->changeItem($scrumItem);

			$result = $this->mergeList(ItemChecklistFacade::class, $scrumItem->getId(), $items);

			if ($result->isSuccess())
			{
				return '';
			}
			else
			{
				$this->errorCollection->setError(
					new Error(
						Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_SAVE_ITEM_ERROR'),
						self::ERROR_COULD_NOT_SAVE_ITEM_LIST
					)
				);

				return null;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));

			return null;
		}
	}

	private function convertTypeItems(int $itemId, array $typeItems): array
	{
		$items = [];

		$typeItems = $this->generateNodeIdForItems($typeItems);

		$parentsMap = $this->getParentsMap($typeItems);

		foreach ($typeItems as $typeItem)
		{
			$items[$typeItem['NODE_ID']] = [
				'ITEM_ID' => $itemId,
				'NODE_ID' => $typeItem['NODE_ID'],
				'PARENT_NODE_ID' => ($parentsMap[$typeItem['PARENT_ID']] ?? 0),
				'TITLE' => $typeItem['TITLE'],
				'ACTION' => [
					'MODIFY' => false,
					'REMOVE' => false,
					'TOGGLE' => true
				]
			];
		}

		return $items;
	}

	private function isItemListEmpty(int $itemId): bool
	{
		try
		{
			return empty(ItemChecklistFacade::getItemsForEntity($itemId, $this->executiveUserId));
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_IS_EMPTY)
			);

			return false;
		}
	}

	private function getItemItems(int $itemId): array
	{
		$items = [];

		try
		{
			$items = ItemChecklistFacade::getItemsForEntity($itemId, $this->executiveUserId);
			foreach (array_keys($items) as $id)
			{
				$items[$id]['COPIED_ID'] = $id;
				unset($items[$id]['ID']);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_DATA));
		}

		return $items;
	}

	private function generateNodeIdForItems(array $items): array
	{
		$randomGenerator = new RandomSequence(rand());

		foreach ($items as $itemId => $item)
		{
			$items[$itemId]['NODE_ID'] = mb_strtolower($randomGenerator->randString(9));
		}

		return $items;
	}

	private function getParentsMap(array $items): array
	{
		$parentsMap = [];

		foreach ($items as $itemId => $item)
		{
			if ((int) $item['PARENT_ID'] === 0)
			{
				$parentsMap[$itemId] = $item['NODE_ID'];
			}
		}

		return $parentsMap;
	}
}