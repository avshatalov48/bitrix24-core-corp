<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Tasks\Scrum\Checklist\EntityChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Internal\EntityInfoColumn;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User as TasksUserUtil;

class DefinitionOfDoneService extends Controller
{
	const ERROR_COULD_NOT_MERGE_LIST = 'TASKS_DOD_01';
	const ERROR_COULD_NOT_GET_DATA = 'TASKS_DOD_02';
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_DOD_03';
	const ERROR_COULD_NOT_ADD_DEFAULT_LIST = 'TASKS_DOD_04';
	const ERROR_COULD_NOT_SAVE_ITEM_LIST = 'TASKS_DOD_05';
	const ERROR_COULD_NOT_SAVE_DOD_REQUIRED_OPTION = 'TASKS_DOD_06';
	const ERROR_COULD_NOT_READ_DOD_REQUIRED_OPTION = 'TASKS_DOD_07';

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

	public function getComponent(int $entityId, string $entityType, array $items): Component
	{
		$randomGenerator = new RandomSequence(rand());

		return new Component('bitrix:tasks.widget.checklist.new', '', [
			'ENTITY_ID' => $entityId,
			'ENTITY_TYPE' => $entityType,
			'DATA' => $items,
			'CONVERTED' => true,
			'CAN_ADD_ACCOMPLICE' => false,
			'SIGNATURE_SEED' => $randomGenerator->randString(6)
		]);
	}

	public function getItemsByEntityId(int $entityId): array
	{
		$items = [];

		try
		{
			$items = EntityChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId);
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

	public function isListEmpty(int $entityId): bool
	{
		try
		{
			return empty(EntityChecklistFacade::getItemsForEntity($entityId, $this->executiveUserId));
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_IS_EMPTY));
			return false;
		}
	}

	public function createDefaultList(int $entityId): void
	{
		try
		{
			$result = EntityChecklistFacade::add($entityId, $this->executiveUserId, [
				'TITLE' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_0'),
				'IS_COMPLETE' => 'N',
				'PARENT_ID' => 0
			]);
			$newItem = $result->getData()['ITEM'];
			$newItemId = $newItem->getFields()['ID'];
			for ($i = 1; $i <= 10; $i++)
			{
				EntityChecklistFacade::add($entityId, $this->executiveUserId, [
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
				EntityChecklistFacade::deleteByEntityId($entityId, $this->executiveUserId);
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

	public function saveRequiredOption(int $entityId, string $value): bool
	{
		try
		{
			$entity = EntityTable::createEntityObject();
			$entity->setId($entityId);

			$entityInfo = new EntityInfoColumn();
			$entityInfo->setDodItemsRequired($value);
			$entity->setInfo($entityInfo);

			$result = EntityTable::update($entity->getId(), $entity->getFieldsToUpdateEntity());

			return $result->isSuccess();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_SAVE_DOD_REQUIRED_OPTION)
			);
		}

		return false;
	}

	public function getRequiredOption(int $entityId): string
	{
		try
		{
			return $this->getRequiredOptionValue($entityId);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_DOD_REQUIRED_OPTION)
			);
		}

		return '';
	}

	public function getListOptionsAction(): ?array
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$backlogService = new BacklogService();
			$backlog = $backlogService->getBacklogByGroupId($groupId);
			if ($backlogService->getErrors() || $backlog->isEmpty())
			{
				return null;
			}

			return [
				'requiredOption' => $this->getRequiredOptionValue($backlog->getId())
			];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_DOD_REQUIRED_OPTION)
			);
		}

		return null;
	}

	public function saveListAction()
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);

			$this->executiveUserId = (int) TasksUserUtil::getId();

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

	public function getItemComponentAction(): ?Component
	{
		try
		{
			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);

			$backlogService = new BacklogService();
			$backlog = $backlogService->getBacklogByGroupId($groupId);
			if ($backlogService->getErrors() || $backlog->isEmpty())
			{
				return null;
			}

			if ($this->isListEmpty($backlog->getId()))
			{
				$this->createDefaultList($backlog->getId());
			}

			$entityItems = $this->getItemsByEntityId($backlog->getId());

			$itemService = new ItemService();
			$item = $itemService->getItemBySourceId($taskId);
			if ($item->isEmpty())
			{
				$this->errorCollection->setError(
					new Error('System error', self::ERROR_COULD_NOT_READ_DOD_REQUIRED_OPTION)
				);
				return null;
			}

			$items = $this->getItemItemsByEntityItems($item->getId(), $entityItems);

			return $this->getComponent($item->getId(), 'SCRUM_ITEM', $items);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return null;
		}
	}

	public function getTaskCompleteButtonsAction(): ?Component
	{
		try
		{
			if (!ModuleManager::isModuleInstalled('ui'))
			{
				throw new SystemException('Cannot connect required modules');
			}

			return new Component('bitrix:ui.button.panel', '', [
				'FRAME' => true,
				'BUTTONS' => [
					[
						'ID' => 'complete',
						'TYPE' => 'save',
						'CAPTION' => Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_TASK_COMPLETE_BUTTON')
					],
					[
						'type' => 'custom',
						'layout' => $this->getCancelButtonLayout(),
					],
				]
			]);
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	private function getItemItemsByEntityItems(int $itemId, array $entityItems): array
	{
		$items = [];

		$entityItems = $this->generateNodeIdForItems($entityItems);

		$parentsMap = $this->getParentsMap($entityItems);

		foreach ($entityItems as $entityItem)
		{
			$parentId = (isset($parentsMap[$entityItem['PARENT_ID']]) ? $parentsMap[$entityItem['PARENT_ID']] : 0);
			$items[$entityItem['NODE_ID']] = [
				'ITEM_ID' => $itemId,
				'NODE_ID' => $entityItem['NODE_ID'],
				'PARENT_NODE_ID' => $parentId,
				'TITLE' => $entityItem['TITLE'],
				'ACTION' => [
					'MODIFY' => false,
					'REMOVE' => false,
					'TOGGLE' => true
				]
			];
		}

		return $items;
	}

	private function generateNodeIdForItems(array $entityItems): array
	{
		$randomGenerator = new RandomSequence(rand());

		foreach ($entityItems as $itemId => &$entityItem)
		{
			$entityItem['NODE_ID'] = mb_strtolower($randomGenerator->randString(9));
		}

		return $entityItems;
	}

	private function getParentsMap(array $entityItems): array
	{
		$parentsMap = [];

		foreach ($entityItems as $itemId => $entityItem)
		{
			if ((int) $entityItem['PARENT_ID'] === 0)
			{
				$parentsMap[$itemId] = $entityItem['NODE_ID'];
			}
		}

		return $parentsMap;
	}

	private function getRequiredOptionValue(int $entityId): string
	{
		$queryObject = EntityTable::getList([
			'select' => ['INFO'],
			'filter' => [
				'ID' => (int) $entityId
			]
		]);
		if ($entityData = $queryObject->fetch())
		{
			/** @var EntityInfoColumn $entityInfo */
			$entityInfo = $entityData['INFO'];
			return $entityInfo->getDodItemsRequired();
		}
		else
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_READ_REQUIRED_OPTION_ERROR'),
					self::ERROR_COULD_NOT_READ_DOD_REQUIRED_OPTION
				)
			);

			return '';
		}
	}

	private function getCancelButtonLayout(): string
	{
		return '<a class="ui-btn ui-btn-link" name="cancel">'
			.Loc::getMessage('TASKS_SCRUM_DEFINITION_OF_DONE_TASK_CANCEL_BUTTON').'</a>';
	}
}