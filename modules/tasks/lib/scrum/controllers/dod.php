<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Util\User;

class DoD extends Controller
{
	const ERROR_COULD_NOT_SAVE_SETTINGS = 'TASKS_DDC_01';
	const ERROR_COULD_NOT_GET_DATA = 'TASKS_DDC_02';
	const ERROR_COULD_NOT_SAVE_ITEM_LIST = 'TASKS_DDC_03';
	const ERROR_COULD_NOT_READ_DOD_SETTINGS = 'TASKS_DDC_04';
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_DDC_05';

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->errorCollection = new ErrorCollection;
	}

	public function getSettingsAction(): ?array
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$typeService = new TypeService();
			$backlogService = new BacklogService();
			$itemService = new ItemService();

			$backlog = $backlogService->getBacklogByGroupId($groupId);

			$types = [];
			foreach ($typeService->getTypes($backlog->getId()) as $type)
			{
				$types[] = $typeService->getTypeData($type);
			}

			$activeTypeId = 0;
			if ($taskId)
			{
				$item = $itemService->getItemBySourceId($taskId);
				$activeTypeId = $item->getTypeId();
			}

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

	public function getChecklistAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$userId = User::getId();

			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);

			$definitionOfDoneService = new DefinitionOfDoneService($userId);

			$items = $definitionOfDoneService->getTypeItems($typeId);

			return $definitionOfDoneService->getComponent($typeId, 'SCRUM_ENTITY', $items);
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function saveSettingsAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$userId = User::getId();

			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);
			$requiredOption = (is_string($post['requiredOption']) ? $post['requiredOption'] : 'N');

			$definitionOfDoneService = new DefinitionOfDoneService($userId);

			$result = $definitionOfDoneService->mergeList(TypeChecklistFacade::class, $typeId, $items);

			$result->setData(
				array_merge(($result->getData() ?? []), ['OPEN_TIME' => (new DateTime())->getTimestamp()])
			);

			$typeService = new TypeService();

			$type = $typeService->getTypeObject();
			$type->setId($typeId);
			$type->setDodRequired($requiredOption);

			$typeService->changeType($type);

			if ($result->isSuccess())
			{
				return '';
			}
			else
			{
				$this->errorCollection->setError(
					new Error(
						'System error',
						self::ERROR_COULD_NOT_SAVE_SETTINGS
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

	public function getListAction(): ?Component
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$backlogService = new BacklogService();
			$itemService = new ItemService();
			$definitionOfDoneService = new DefinitionOfDoneService($userId);

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

			if ($this->isItemListEmpty($item->getId(), $userId) || $item->getTypeId() !== $typeId)
			{
				$typeItems = $definitionOfDoneService->getTypeItems($typeId);

				$items = $this->convertTypeItems($item->getId(), $typeItems);
			}
			else
			{
				$items = $this->getItemItems($item->getId(), $userId);
			}

			return $definitionOfDoneService->getComponent($item->getId(), 'SCRUM_ITEM', $items);
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
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$typeId = (is_numeric($post['typeId']) ? (int) $post['typeId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);
			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$itemService = new ItemService();
			$typeService = new TypeService();
			$definitionOfDoneService = new DefinitionOfDoneService($userId);

			$type = $typeService->getType($typeId);

			$scrumItem = $itemService->getItemBySourceId($taskId);

			if ($scrumItem->isEmpty() || $type->isEmpty())
			{
				$this->errorCollection->setError(
					new Error(
						Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_SAVE_ITEM_ERROR'),
						self::ERROR_COULD_NOT_SAVE_ITEM_LIST
					)
				);

				return null;
			}

			$scrumItem->setTypeId($type->getId());

			$itemService->changeItem($scrumItem);

			$result = $definitionOfDoneService->mergeList(
				ItemChecklistFacade::class,
				$scrumItem->getId(),
				$items
			);

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

	public function getDodInfoAction(): ?array
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				return null;
			}

			$typeService = new TypeService();
			$backlogService = new BacklogService();

			$backlog = $backlogService->getBacklogByGroupId($groupId);

			$types = [];
			foreach ($typeService->getTypes($backlog->getId()) as $type)
			{
				$types[] = $typeService->getTypeData($type);
			}

			return [
				'existsDod' => (!empty($types)),
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

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
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

	private function isItemListEmpty(int $itemId, int $userId): bool
	{
		try
		{
			return empty(ItemChecklistFacade::getItemsForEntity($itemId, $userId));
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_IS_EMPTY
				)
			);

			return false;
		}
	}

	private function getItemItems(int $itemId, int $userId): array
	{
		$items = [];

		try
		{
			$items = ItemChecklistFacade::getItemsForEntity($itemId, $userId);
			foreach (array_keys($items) as $id)
			{
				$items[$id]['COPIED_ID'] = $id;
				unset($items[$id]['ID']);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(),
					self::ERROR_COULD_NOT_GET_DATA
				)
			);
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