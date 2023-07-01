<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Form\TypeForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Util\User;

class DoD extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_SDC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_SDC_02';
	const ERROR_COULD_NOT_SAVE_SETTINGS = 'TASKS_SDC_03';
	const ERROR_COULD_NOT_GET_DATA = 'TASKS_SDC_04';
	const ERROR_COULD_NOT_SAVE_ITEM_LIST = 'TASKS_SDC_05';
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_SDC_06';

	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId'] ?? null) ? (int) $post['groupId'] : 0);
		$taskId = (is_numeric($post['taskId'] ?? null) ? (int) $post['taskId'] : 0);
		$userId = User::getId();

		if ($taskId && !TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		if (!$taskId && !Group::canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * The method checks whether dod should be shown for the task.
	 *
	 * @param int $groupId Group id.
	 * @param int $taskId Task id.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isNecessaryAction(int $groupId, int $taskId): bool
	{
		$userId = User::getId();

		if (!Group::canReadGroupTasks($userId, $groupId))
		{
			return false;
		}

		$taskService = new TaskService($userId);

		$task = current($taskService->getTasksInfo([$taskId]));
		if (!$task)
		{
			return false;
		}

		$parentId = (int) $task['PARENT_ID'];

		return $this->isTmpNecessary($groupId, $parentId);

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

		$type = $this->getItemType($taskId);

		if (!$type->isEmpty())
		{
			if ($this->isNecessaryForTask($groupId, $userId, $type))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		if ($this->isNecessaryForScrum($groupId, $userId))
		{
			return true;
		}

		return false;
	}

	// todo tmp
	private function isTmpNecessary(int $groupId, int $parentId): bool
	{
		if ($parentId)
		{
			$queryObject = TaskTable::getList([
				'filter' => [
					'ID' => $parentId,
					'GROUP_ID' => $groupId,
				],
				'select' => [ 'ID' ],
			]);

			if (!$queryObject->fetch())
			{
				return true;
			}
		}
		else
		{
			return true;
		}

		return false;
	}

	/**
	 * @param int $groupId Group id.
	 * @param int $taskId Task id.
	 * @param string $saveRequest If the request comes from a place where the list may in advance saved.
	 * @return array
	 */
	public function getSettingsAction(int $groupId, int $taskId = 0, string $saveRequest = 'Y'): array
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$itemType = $this->getItemType($taskId);

		$activeTypeId = 0;
		$types = [];

		foreach ($typeService->getTypes($backlog->getId()) as $type)
		{
			$types[] = $type->toArray();
		}

		if (!$itemType->isEmpty())
		{
			$activeTypeId = $itemType->getId();
		}

		return [
			'types' => $types,
			'activeTypeId' => $activeTypeId,
		];
	}

	/**
	 * Returns the component displaying the dod list.
	 *
	 * @param int $typeId Type id.
	 * @return Component
	 */
	public function getChecklistAction(int $typeId): ?Component
	{
		$userId = User::getId();

		$typeService = new TypeService();
		$entityService = new EntityService();

		$type = $typeService->getType($typeId);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_TYPE_NOT_FOUND'),
					self::ERROR_COULD_NOT_SAVE_SETTINGS
				)
			);

			return null;
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$definitionOfDoneService = new DefinitionOfDoneService($userId);

		$items = $definitionOfDoneService->getTypeItems($typeId);

		return $definitionOfDoneService->getComponent($typeId, 'SCRUM_ENTITY', $items);
	}

	/**
	 * Saves the dod list settings.
	 *
	 * @param int $typeId Type id.
	 * @param string $requiredOption Y|N Is the dod list required.
	 * @param array $items An array with a list of items that forms the dod list component.
	 * @param array $participants An array with a list of participants to whom you will show the form.
	 * @return string|null
	 */
	public function saveSettingsAction(
		int $typeId,
		string $requiredOption,
		array $items = [],
		array $participants = []
	): ?array
	{
		$userId = User::getId();

		$typeService = new TypeService();
		$entityService = new EntityService();

		$type = $typeService->getType($typeId);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_TYPE_NOT_FOUND'),
					self::ERROR_COULD_NOT_SAVE_SETTINGS
				)
			);

			return null;
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$definitionOfDoneService = new DefinitionOfDoneService($userId);

		$result = $definitionOfDoneService->mergeList(TypeChecklistFacade::class, $type->getId(), $items);

		$result->setData(
			array_merge(($result->getData() ?? []), ['OPEN_TIME' => (new DateTime())->getTimestamp()])
		);

		$typeForm = new TypeForm();

		$typeForm->setId($type->getId());
		$typeForm->setDodRequired($requiredOption);

		if ($typeService->changeType($typeForm))
		{
			$typeForm->setParticipantsList($participants);

			if ($type->getParticipantsCodes() !== $typeForm->getParticipantsCodes())
			{
				$typeService->saveParticipants($typeForm);
			}
		}

		if ($result->isSuccess())
		{
			return [
				'type' => $typeService->getType($type->getId())->toArray(),
			];
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

	/**
	 * Returns the component displaying the dod list for task.
	 *
	 * @param int $groupId Group id.
	 * @param int $taskId Task id.
	 * @param int $typeId Type id.
	 * @return Component|null
	 */
	public function getListAction(int $groupId, int $taskId, int $typeId): ?Component
	{
		$userId = User::getId();

		$backlogService = new BacklogService();
		$itemService = new ItemService();
		$typeService = new TypeService();
		$entityService = new EntityService();

		$type = $typeService->getType($typeId);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_TYPE_NOT_FOUND'),
					self::ERROR_COULD_NOT_SAVE_SETTINGS
				)
			);

			return null;
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$definitionOfDoneService = new DefinitionOfDoneService($userId);

		$backlog = $backlogService->getBacklogByGroupId($groupId);
		$item = $itemService->getItemBySourceId($taskId);

		if ($item->isEmpty() || $backlogService->getErrors() || $backlog->isEmpty())
		{
			$this->errorCollection->setError(new Error('System error', self::ERROR_COULD_NOT_GET_DATA));

			return null;
		}

		if (
			$this->isItemListEmpty($item->getId(), $userId)
			|| $item->getTypeId() !== $typeId
		)
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

	/**
	 * Saves a dod list for a specific task.
	 *
	 * @param int $taskId Task id.
	 * @param int $typeId Type id.
	 * @param array $items An array with a list of items that forms the dod list component.
	 * @return string|null
	 */
	public function saveListAction(int $taskId, int $typeId, array $items = []): ?string
	{
		$userId = User::getId();

		$itemService = new ItemService();
		$typeService = new TypeService();
		$entityService = new EntityService();
		$definitionOfDoneService = new DefinitionOfDoneService($userId);

		$type = $typeService->getType($typeId);
		if ($type->isEmpty())
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_TYPE_NOT_FOUND'),
					self::ERROR_COULD_NOT_SAVE_SETTINGS
				)
			);

			return null;
		}

		$entity = $entityService->getEntityById($type->getEntityId());
		if (!Group::canReadGroupTasks($userId, $entity->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SDC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

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

	/**
	 * Returns a data that the application might need.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 */
	public function getDodInfoAction(int $groupId): array
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$types = [];
		foreach ($typeService->getTypes($backlog->getId()) as $type)
		{
			$types[] = $type->toArray();
		}

		return [
			'existsDod' => (!empty($types)),
		];
	}

	private function convertTypeItems(int $itemId, array $typeItems): array
	{
		$items = [];

		$typeItems = $this->generateNodeIdForItems($typeItems);

		foreach ($typeItems as $typeItem)
		{
			$items[$typeItem['NODE_ID']] = [
				'ITEM_ID' => $itemId,
				'NODE_ID' => $typeItem['NODE_ID'],
				'PARENT_NODE_ID' => (
					isset($typeItems[$typeItem['PARENT_ID']])
						? $typeItems[$typeItem['PARENT_ID']]['NODE_ID']
						: 0
				),
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
				new Error(
					$exception->getMessage(),
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

	private function getItemType(int $taskId): TypeForm
	{
		$itemService = new ItemService();
		$typeService = new TypeService();

		$item = $itemService->getItemBySourceId($taskId);

		return $typeService->getType($item->getTypeId());
	}

	private function isNecessaryForTask(int $groupId, int $userId, TypeForm $type): bool
	{
		$participantsIds = [];

		if (!$type->isEmpty())
		{
			$participantsIds = $this->getParticipantsIds($groupId, $type->getParticipantsCodes());
		}

		return in_array($userId, $participantsIds);
	}

	private function isNecessaryForScrum(int $groupId, int $userId): bool
	{
		$typeService = new TypeService();
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		$participantsCodes = [];
		foreach ($typeService->getTypes($backlog->getId()) as $type)
		{
			$participantsCodes = array_merge($participantsCodes, $type->getParticipantsCodes());
		}

		$participantsIds = $this->getParticipantsIds($groupId, $participantsCodes);

		return in_array($userId, $participantsIds);
	}

	private function getParticipantsIds(int $groupId, array $participantsCodes): array
	{
		$participantsIds = [];

		$scrumMasterRole = 'M';

		$group = Workgroup::getById($groupId);

		$scrumMasterId = (int) $group->getScrumMaster();

		foreach ($participantsCodes as $code)
		{
			if (mb_substr($code, 0, 1) === 'U')
			{
				$userId = (int) mb_substr($code, 1);
				if (!in_array($userId, $participantsIds))
				{
					$participantsIds[] = $userId;
				}
			}
			elseif (preg_match('/^SG([0-9]+)_?([AEKM])?$/', $code, $match) && isset($match[2]))
			{
				$role = $match[2];
				if ($role === $scrumMasterRole)
				{
					$participantsIds[] = $scrumMasterId;
				}
				else
				{
					$participantsIds = array_merge(
						$participantsIds,
						$this->getSonetUserIds($groupId, $role, $scrumMasterId)
					);
				}
			}
		}

		return $participantsIds;
	}

	private function getSonetUserIds(int $groupId, string $role, int $scrumMasterId): array
	{
		$userIds = [];

		$queryObject = \CSocNetUserToGroup::getList(
			['RAND' => 'ASC'],
			[
				'GROUP_ID' => $groupId,
				'=ROLE' => $role,
				'USER_ACTIVE' => 'Y'
			],
			false,
			false,
			[
				'ID',
				'USER_ID',
			]
		);

		if ($queryObject)
		{
			while ($userData = $queryObject->fetch())
			{
				$userId = (int) $userData['USER_ID'];
				if ($role === SONET_ROLES_MODERATOR && $userId === $scrumMasterId)
				{
					continue;
				}

				$userIds[] = $userId;
			}
		}

		return $userIds;
	}
}