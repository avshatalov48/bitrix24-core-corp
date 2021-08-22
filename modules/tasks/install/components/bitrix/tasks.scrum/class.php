<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Grid;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Scrum\Checklist\TypeChecklistFacade;
use Bitrix\Tasks\Scrum\Checklist\ItemChecklistFacade;
use Bitrix\Tasks\Scrum\Internal\EntityInfoColumn;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemInfoColumn;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\CacheService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PullService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\RobotService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Scrum\Utility\BurnDownChart;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction;

class TasksScrumComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TSC_01';
	const ERROR_REQUIRED_PARAMETERS = 'TASKS_TSC_02';

	private $application;
	private $errorCollection;
	private $userId;

	private $debugMode = false;
	private $frameMode = false;

	/**
	 * @var CUserTypeManager
	 */
	private $userFieldManager;

	private $filteredTaskIds = [];

	public function __construct($component = null)
	{
		parent::__construct($component);

		global $APPLICATION;
		$this->application = $APPLICATION;

		global $USER_FIELD_MANAGER;
		$this->userFieldManager = $USER_FIELD_MANAGER;

		$this->errorCollection = new ErrorCollection();
	}

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'GROUP_ID',
			'OWNER_ID',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_USER_TASKS',
		];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @throws ArgumentNullException
	 */
	public function onPrepareComponentParams($params)
	{
		if (empty($params['GROUP_ID']))
		{
			throw new ArgumentNullException('GROUP_ID');
		}
		$params['GROUP_ID'] = (is_numeric($params['GROUP_ID']) ? (int) $params['GROUP_ID'] : 0);

		$params['SET_TITLE'] = (isset($params['SET_TITLE']) && $params['SET_TITLE'] == 'Y');

		$params['OWNER_ID'] = (!empty($params['USER_ID']) ? (int)$params['USER_ID'] : 0);

		$params['PATH_TO_USER_TASKS'] = ($params['PATH_TO_USER_TASKS'] ?? '');
		$params['PATH_TO_GROUP_TASKS'] = ($params['PATH_TO_GROUP_TASKS'] ?? '');

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkModules();

			$groupId = (int) $this->arParams['GROUP_ID'];

			$this->setTitle();
			$this->init();

			if (!$this->canReadGroupTasks($groupId))
			{
				$this->includeErrorTemplate(Loc::getMessage('TASKS_SCRUM_ACCESS_TO_GROUP_DENIED'));

				return;
			}

			$request = Context::getCurrent()->getRequest();

			$this->debugMode = ($request->get('debug') == 'y');
			$this->frameMode = ($request->get('IFRAME') == 'Y');

			$this->saveActiveTab();
			$activeTab = $this->getActiveTab($groupId);

			$this->arResult['debugMode'] = ($this->debugMode ? 'Y' : 'N');
			$this->arResult['frameMode'] = ($this->frameMode ? 'Y' : 'N');

			$this->arResult['views'] = $this->getViewsInfo($groupId);

			$this->subscribeUserToPull($this->userId, $groupId);

			switch ($activeTab)
			{
				case 'plan':
					$this->includePlanTemplate($groupId);
					break;
				case 'active_sprint':
					$this->includeActiveSprintTemplate($groupId);
					break;
				case 'completed_sprint':
					$sprintId = (int) $request->get('sprintId');
					$this->includeCompletedSprintTemplate($groupId, $sprintId);
					break;
			}
		}
		catch (SystemException $exception)
		{
			$this->includeErrorTemplate($exception->getMessage());
		}
	}

	public function applyFilterAction()
	{
		try
		{
			$this->checkModules();

			$userId = Util\User::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$itemService = new ItemService();
			$taskService = new TaskService($userId, $this->application);
			$taskService->setOwnerId($this->arParams['OWNER_ID']);

			$filterInstance = $taskService->getFilterInstance(
				$groupId,
				$request->get('tab') === 'completed_sprint'
			);
			$filter = $taskService->getFilter($filterInstance);

			$epicTaskIds = $this->getEpicTaskIdsFromFilter($filter);

			$taskIds = $taskService->getTaskIdsByFilter($filter);

			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			if ($epicTaskIds)
			{
				$taskIds = array_intersect($epicTaskIds, $taskIds);
			}

			$items = [];
			if ($taskIds)
			{
				$userService = new UserService();
				$backlogService = new BacklogService();
				$sprintService = new SprintService();

				$backlog = $backlogService->getBacklogByGroupId($groupId);
				$backlogItemIds = $itemService->getItemIdsBySourceIds($taskIds, $backlog->getId());

				$backlogItems = $this->getItemsData($backlogItemIds, $itemService, $taskService, $userService);

				$allSprintItems = [];

				$listSprints = $sprintService->getSprintsByGroupId($groupId);
				foreach ($listSprints as $sprint)
				{
					if ($sprint->isCompletedSprint() && !$filterInstance->isSearchFieldApplied())
					{
						continue;
					}
					$sprintItemIds = $itemService->getItemIdsBySourceIds($taskIds, $sprint->getId());
					$sprintItems = $this->getItemsData($sprintItemIds, $itemService, $taskService, $userService);
					$allSprintItems = array_merge($allSprintItems, $sprintItems);
				}

				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
					return null;
				}
				if ($backlogService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $backlogService->getErrors());
					return null;
				}
				if ($taskService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
					return null;
				}

				$items = array_merge($backlogItems, $allSprintItems);
			}

			return $items;
		}
		catch (SystemException $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
		}

		return [];
	}

	public function createTaskAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$tmpId = (is_string($post['tmpId'] ) ? $post['tmpId'] : '');
			$name = (is_string($post['name'] ) ? $post['name'] : 'The task');
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);
			$entityType = (is_string($post['entityType']) ? $post['entityType'] : 'backlog');
			$epicId = (is_numeric($post['parentId']) ? (int)$post['parentId'] : 0);
			$parentTaskId = (is_numeric($post['parentTaskId']) ? (int)$post['parentTaskId'] : 0);
			$storyPoints = (is_string($post['storyPoints']) ? $post['storyPoints'] : '');
			$sort = (is_numeric($post['sort']) ? (int)$post['sort'] : 0);
			$responsible = (is_array($post['responsible']) ? $post['responsible'] : []);
			$tags = (is_array($post['tags']) ? $post['tags'] : []);
			$isActiveSprint = (isset($post['isActiveSprint']) && $post['isActiveSprint'] === 'Y');
			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);
			$info = (is_array($post['info']) ? $post['info'] : []);

			$groupId = $this->arParams['GROUP_ID'];

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$taskService = new TaskService($this->userId, $this->application);

			$item = ItemTable::createItemObject();
			$item->setEntityId($entityId);
			if ($epicId)
			{
				$item->setParentId($epicId);
			}
			$item->setSort($sort);
			$item->setCreatedBy($this->userId);
			$item->setStoryPoints($storyPoints);
			$item->setItemType(ItemTable::TASK_TYPE);

			$responsibleId = (is_numeric($responsible['id']) ? (int) $responsible['id'] : 0);
			if (!$responsibleId)
			{
				$responsibleId = $this->getDefaultResponsibleId($groupId);
			}

			$taskFields = [
				'TITLE' => $name,
				'CREATED_BY' => $this->userId,
				'RESPONSIBLE_ID' => $responsibleId,
				'GROUP_ID' => $groupId,
				'TAGS' => $tags,
			];

			$isDecompositionAction = ($parentTaskId > 0);

			if ($isDecompositionAction && $entityType === 'sprint')
			{
				$taskFields['PARENT_ID'] = $parentTaskId;

				$parentItem = $itemService->getItemBySourceId($parentTaskId);
				$parentItem->setStoryPoints('');

				$itemService->changeItem($parentItem);
			}

			$taskId = $taskService->createTask($taskFields);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $taskService->getErrors());
				return null;
			}

			if ($isDecompositionAction && $entityType === 'backlog')
			{
				$taskService->updateTaskLinks($parentTaskId, $taskId);
				$taskService->updateTaskLinks($taskId, $parentTaskId);
			}

			$createdItem = $itemService->getItemBySourceId($taskId);
			$item->setId($createdItem->getId());
			$item->setSourceId($createdItem->getSourceId());
			$item->setTmpId($tmpId);

			$itemInfo = new ItemInfoColumn();
			if (!empty($info[$itemInfo->getBorderColorKey()]))
			{
				$itemInfo->setBorderColor($info[$itemInfo->getBorderColorKey()]);
				$item->setInfo($itemInfo);
			}

			$itemService->changeItem($item, $pushService);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());
				return null;
			}

			if ($isActiveSprint)
			{
				$kanbanService = new KanbanService();
				$kanbanService->addTasksToKanban($entityId, [$taskId]);
				if ($kanbanService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $kanbanService->getErrors());
					return null;
				}
			}

			if ($sortInfo)
			{
				$itemService->sortItems($sortInfo, $pushService);
				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());
					return null;
				}
			}

			return $this->getItemsData([$item->getId()], $itemService, $taskService, new UserService())[0];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), [], $exception);
			return null;
		}
	}

	public function getCurrentStateAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$groupId = $this->arParams['GROUP_ID'];
			$ownerId = $this->arParams['OWNER_ID'];

			$taskId = (is_numeric($post['taskId']) ? (int)$post['taskId'] : 0);

			$itemService = new ItemService();
			$taskService = new TaskService($this->userId);
			$taskService->setOwnerId($ownerId);

			$item = $itemService->getItemBySourceId($taskId);

			return [
				'itemData' => $this->getItemsData([$item->getId()], $itemService, $taskService, new UserService())[0],
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function hasTaskInFilterAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$groupId = $this->arParams['GROUP_ID'];

			$taskId = (is_numeric($post['taskId']) ? (int)$post['taskId'] : 0);

			$taskService = new TaskService($this->userId);

			$filterInstance = $taskService->getFilterInstance($groupId);

			$filter = $taskService->getFilter($filterInstance);

			$filter['ID'] = $taskId;
			$filter['CHECK_PERMISSIONS'] = 'N';

			return [
				'has' => !empty($taskService->getTaskIdsByFilter($filter)),
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function attachFilesToTaskAction()
	{
		try
		{
			$this->checkModules();
			if (!ModuleManager::isModuleInstalled('disk'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$attachedIds = (is_array($post['attachedIds']) ? $post['attachedIds'] : []);
			$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$taskService = new TaskService($this->userId, $this->application);
			$ufValue = $taskService->attachFilesToTask($this->userFieldManager, $taskId, $attachedIds);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ATTACH_FILES_ERROR'), $taskService->getErrors());
				return null;
			}

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setEntityId($entityId);
			$itemService->changeItem($item, $pushService);

			return [
				'attachedFilesCount' => count($ufValue)
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ATTACH_FILES_ERROR'), [], $exception);
			return null;
		}
	}

	public function attachTagToTaskAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$tag = (is_string($post['tag']) ? $post['tag'] : '');
			$itemId = (is_numeric($post['itemId']) ? (int)$post['itemId'] : 0);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$taskService = new TaskService($this->userId, $this->application);
			$taskService->updateTagsList($taskId, [$tag]);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'), $taskService->getErrors());
				return null;
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setEntityId($entityId);

			if ($pushService)
			{
				$pushService->sendUpdateItemEvent($item);
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'));
			return null;
		}
	}

	public function batchAttachTagToTaskAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$tasks = (is_array($post['tasks']) ? $post['tasks'] : []);
			$tag = (is_string($post['tag']) ? $post['tag'] : '');
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$taskService = new TaskService($this->userId, $this->application);

			$itemIds = [];
			foreach ($tasks as $task)
			{
				$taskId = (is_numeric($task['taskId']) ? (int) $task['taskId'] : 0);
				$taskService->updateTagsList($taskId, [$tag]);
				$itemIds[] =  (is_numeric($task['itemId']) ? (int)$task['itemId'] : 0);
			}

			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'), $taskService->getErrors());
				return null;
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			foreach ($itemIds as $itemId)
			{
				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setEntityId($entityId);

				if ($pushService)
				{
					$pushService->sendUpdateItemEvent($item);
				}
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'));
			return null;
		}
	}

	public function deAttachTagToTaskAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$tag = (is_string($post['tag']) ? $post['tag'] : '');
			$itemId = (is_numeric($post['itemId']) ? (int)$post['itemId'] : 0);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$taskService = new TaskService($this->userId, $this->application);
			if ($taskId && $tag)
			{
				$taskService->removeTags($taskId, $tag);
			}
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setEntityId($entityId);

			if ($pushService)
			{
				$pushService->sendUpdateItemEvent($item);
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function batchDeattachTagToTaskAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$tasks = (is_array($post['tasks']) ? $post['tasks'] : []);
			$tag = (is_string($post['tag']) ? $post['tag'] : '');
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$taskService = new TaskService($this->userId);
			$itemIds = [];
			foreach ($tasks as $task)
			{
				$taskId = (is_numeric($task['taskId']) ? (int)$task['taskId'] : 0);
				if ($taskId && $tag)
				{
					$taskService->removeTags($taskId, $tag);
				}
				$itemIds[] =  (is_numeric($task['itemId']) ? (int)$task['itemId'] : 0);
			}

			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			foreach ($itemIds as $itemId)
			{
				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setEntityId($entityId);

				if ($pushService)
				{
					$pushService->sendUpdateItemEvent($item);
				}
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function updateItemEpicAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (is_numeric($post['epicId']) ? (int)$post['epicId'] : 0);
			$itemId = (is_numeric($post['itemId']) ? (int)$post['itemId'] : 0);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setParentId($epicId);
			$item->setEntityId($entityId);

			$itemService->changeItem($item, $pushService);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ATTACH_ERROR'), $itemService->getErrors());
				return null;
			}

			return [
				'epic' => $itemService->getEpicInfo($epicId)
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ATTACH_ERROR'), [], $exception);
			return null;
		}
	}

	public function batchUpdateItemEpicAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			foreach ($items as $item)
			{
				$itemId = (is_numeric($item['itemId']) ? (int) $item['itemId'] : 0);

				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setParentId($epicId);
				$item->setEntityId($entityId);

				$itemService->changeItem($item, $pushService);
			}

			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ATTACH_ERROR'), $itemService->getErrors());
				return null;
			}

			return [
				'epic' => $itemService->getEpicInfo($epicId),
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ATTACH_ERROR'), [], $exception);
			return null;
		}
	}

	public function createSprintAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$tmpId = (is_string($post['tmpId'] ) ? $post['tmpId'] : '');
			$name = (is_string($post['name'] ) ? $post['name'] : 'The sprint');
			$sort = (is_numeric($post['sort']) ? (int) $post['sort'] : 0);
			$dateStart = (is_numeric($post['dateStart']) ? (int) $post['dateStart'] : 0);
			$dateEnd = (is_numeric($post['dateEnd']) ? (int) $post['dateEnd'] : 0);

			$groupId = $this->arParams['GROUP_ID'];

			$sprintService = new SprintService();

			$sprint = $this->createSprint($sprintService, [
				'groupId' => $groupId,
				'tmpId' => $tmpId,
				'name' => $name,
				'sort' => $sort,
				'userId' => $this->userId,
				'dateStart' => $dateStart,
				'dateEnd' => $dateEnd,
			]);

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_ADD_ERROR'), $sprintService->getErrors());
				return null;
			}

			return [
				'sprintId' => $sprint->getId()
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_ADD_ERROR'), [], $exception);
			return null;
		}
	}

	public function changeSprintNameAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
			$name = (is_string($post['name'] ) ? $post['name'] : 'The sprint');

			$sprintService = new SprintService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($sprintId);
			$sprint->setName($name);

			$sprintService->changeSprint($sprint, $pushService);

			$sprint = $sprintService->getSprintById($sprintId);
			if ($sprint->isCompletedSprint())
			{
				(new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT))->clean();
			}

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_NAME_ERROR'), $sprintService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_NAME_ERROR'), [], $exception);
			return null;
		}
	}

	public function changeSprintDeadlineAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
			$dateStart = (is_numeric($post['dateStart']) ? (int) $post['dateStart'] : 0);
			$dateEnd = (is_numeric($post['dateEnd']) ? (int) $post['dateEnd'] : 0);

			$sprintService = new SprintService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($sprintId);
			if ($dateStart)
			{
				$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
			}
			if ($dateEnd)
			{
				$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
			}

			$sprintService->changeSprint($sprint, $pushService);

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_DEADLINE_ERROR'), $sprintService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_DEADLINE_ERROR'), [], $exception);
			return null;
		}
	}

	public function getSprintCompletedItemsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$groupId = (int) $this->arParams['GROUP_ID'];

			$inputSprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($inputSprintId);

			$itemService = new ItemService();
			$taskService = new TaskService($this->userId);
			$taskService->setOwnerId($this->arParams['OWNER_ID']);
			$kanbanService = new KanbanService();
			$userService = new UserService();

			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			if ($kanbanService->getErrors())
			{
				$this->setError(
					Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'),
					$kanbanService->getErrors()
				);
				return null;
			}

			//todo it may be needed later
//			$filterInstance = $taskService->getFilterInstance($groupId);
//
//			$filter = $taskService->getFilter($filterInstance);
//
//			$filteredTaskIds = array_merge(
//				$this->getEpicTaskIdsFromFilter($filter),
//				$taskService->getTaskIdsByFilter($filter)
//			);

			$sprintItemIds = $itemService->getItemIdsBySourceIds($finishedTaskIds, $sprint->getId());
			if ($itemService->getErrors())
			{
				$this->setError(
					Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'),
					$itemService->getErrors()
				);
				return null;
			}

			$items = $this->getItemsData($sprintItemIds, $itemService, $taskService, $userService);

			if ($taskService->getErrors())
			{
				$this->setError(
					Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'),
					$taskService->getErrors()
				);
				return null;
			}

			return $items;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'), [], $exception);
			return null;
		}
	}

	public function removeSprintAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$groupId = $this->arParams['GROUP_ID'];

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$sprintService = new SprintService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($sprintId);
			$sprint->setGroupId($groupId);

			$sprintService->removeSprint($sprint, $pushService);

			if ($sortInfo)
			{
				$sprintService->changeSort($sortInfo);
			}

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_REMOVE_ERROR'), $sprintService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_REMOVE_ERROR'), [], $exception);
			return null;
		}
	}

	public function updateItemSortAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$kanbanService = new KanbanService();
			$taskService = new TaskService($this->userId);

			$this->moveTo($taskService, $itemService, $kanbanService, $post);

			if ($sortInfo)
			{
				$itemService->sortItems($sortInfo, $pushService);
			}

			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_SORT_ERROR'), $itemService->getErrors());
				return null;
			}
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_SORT_ERROR'), $kanbanService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_SORT_ERROR'), [], $exception);
			return null;
		}
	}

	public function batchUpdateItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$items = (is_array($post['items']) ? $post['items'] : []);
			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$kanbanService = new KanbanService();
			$taskService = new TaskService($this->userId);

			foreach ($items as $item)
			{
				$this->moveTo($taskService, $itemService, $kanbanService, $item);
				if ($kanbanService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $kanbanService->getErrors());
					return null;
				}
			}

			if ($sortInfo)
			{
				$itemService->sortItems($sortInfo, $pushService);
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), [], $exception);
			return null;
		}
	}

	public function updateItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);
			$itemType = (is_string($post['itemType']) ? $post['itemType'] : ItemTable::TASK_TYPE);
			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);
			$name = (is_string($post['name'] ) ? $post['name'] : '');
			$storyPoints = (is_string($post['storyPoints']) ? $post['storyPoints'] : null);
			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);
			$info = (is_array($post['info']) ? $post['info'] : []);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			switch ($itemType)
			{
				case ItemTable::TASK_TYPE:
					if (strlen($name) > 0)
					{
						$item = $itemService->getItemById($itemId);
						if ($item->isEmpty())
						{
							$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'));
							return null;
						}
						$userId = (int)Util\User::getId();
						$taskService = new TaskService($userId, $this->application);
						$taskService->changeTask($item->getSourceId(), [
							'TITLE' => $name
						]);
						if ($taskService->getErrors())
						{
							$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $taskService->getErrors());
							return null;
						}
					}
					break;
			}

			$item = ItemTable::createItemObject();

			if ($storyPoints !== null)
			{
				$item->setId($itemId);
				$item->setEntityId($entityId);
				$item->setStoryPoints($storyPoints);
				$itemService->changeItem($item);
			}

			$itemInfo = new ItemInfoColumn();
			if (!empty($info[$itemInfo->getBorderColorKey()]))
			{
				$item->setId($itemId);
				$item->setEntityId($entityId);
				$itemInfo->setBorderColor($info[$itemInfo->getBorderColorKey()]);
				$item->setInfo($itemInfo);
			}

			if (!$item->isEmpty())
			{
				$itemService->changeItem($item, $pushService);
			}

			$kanbanService = new KanbanService();
			$taskService = new TaskService($this->userId);
			$this->moveTo($taskService, $itemService, $kanbanService, $post);
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $kanbanService->getErrors());
				return null;
			}

			if ($sortInfo)
			{
				$itemService->sortItems($sortInfo, $pushService);
			}

			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $itemService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), [], $exception);
			return null;
		}
	}

	public function batchRemoveItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$items = (is_array($post['items']) ? $post['items'] : []);
			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			foreach ($items as $item)
			{
				$this->removeItem($item, $pushService);
			}

			if ($sortInfo)
			{
				$itemService = new ItemService();
				$itemService->sortItems($sortInfo, $pushService);
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), [], $exception);
			return null;
		}
	}

	public function removeItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$groupId = $this->arParams['GROUP_ID'];

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$post['entityId'] = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);
			if (!$post['entityId'])
			{
				$backlogService = new BacklogService();

				$backlog = $backlogService->getBacklogByGroupId($groupId);

				$post['entityId'] = $backlog->getId();
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$response = $this->removeItem($post, $pushService);

			if ($sortInfo)
			{
				$itemService = new ItemService();
				$itemService->sortItems($sortInfo, $pushService);
			}

			return $response;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), [], $exception);
			return null;
		}
	}

	public function updateSprintSortAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sortInfo = (is_array($post['sortInfo']) ? $post['sortInfo'] : []);

			$sprintService = new SprintService();
			if ($sortInfo)
			{
				$sprintService->changeSort($sortInfo);
			}

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_SORT_ERROR'), $sprintService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_SORT_ERROR'), [], $exception);
			return null;
		}
	}

	public function startSprintAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$this->userId = (int)Util\User::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($sprintId);
			$sprint->setGroupId($groupId);

			$sprintInfo = new EntityInfoColumn();
			if (!empty($post[$sprintInfo->getSprintGoalKey()]))
			{
				$sprintInfo->setSprintGoal($post[$sprintInfo->getSprintGoalKey()]);
			}
			$sprint->setInfo($sprintInfo);

			$sprintService = new SprintService();
			$itemService = new ItemService();
			$kanbanService = new KanbanService();

			if ($sprintService->isActiveSprint($sprint))
			{
				$this->errorCollection->setError(new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ALREADY_ERROR')));
				return null;
			}

			$taskIds = $itemService->getTaskIdsByEntityId($sprint->getId());
			if (empty($taskIds))
			{
				$this->errorCollection->setError(new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_NOT_TASKS_ERROR')));
				return null;
			}

			$taskService = new TaskService($this->userId);

			$subTaskIds = [];
			foreach ($taskIds as $taskId)
			{
				$subTaskIds = array_merge($subTaskIds, $taskService->getSubTaskIds($groupId, $taskId));
			}
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'), $taskService->getErrors());
				return null;
			}

			$kanbanService->addTasksToKanban($sprint->getId(), $taskIds);
			$kanbanService->addTasksToKanban($sprint->getId(), $subTaskIds);
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'), $kanbanService->getErrors());
				return null;
			}

			if (Loader::includeModule('bizproc'))
			{
				$robotService = new RobotService();

				if ($lastSprintId = $kanbanService->getLastCompletedSprintIdSameGroup($sprint->getId()))
				{
					$stageIdsMap = $kanbanService->getStageIdsMapBetweenTwoSprints($sprint->getId(), $lastSprintId);

					$robotService->updateRobotsOfLastSprint($groupId, $stageIdsMap);
				}

				if ($robotService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'), $robotService->getErrors());

					return null;
				}
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprintService->startSprint($sprint, $pushService);
			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'), $sprintService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ERROR'), [], $exception);
			return null;
		}
	}

	public function completeSprintAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$sprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);
			$isTargetBacklog = (is_string($post['direction']) && $post['direction'] === 'backlog');
			$targetSprintId = (is_numeric($post['direction']) ? (int) $post['direction'] : 0);

			$groupId = $this->arParams['GROUP_ID'];

			if (!$sprintId)
			{
				$this->setError(
					Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'),
					[new Error('', self::ERROR_REQUIRED_PARAMETERS)]
				);
				return null;
			}

			$sprintService = new SprintService();
			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$backlogService = new BacklogService();

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($sprintId);
			$sprint->setGroupId($groupId);
			$sprint->setDateEnd(DateTime::createFromTimestamp(time()));

			$taskService = new TaskService($this->userId);

			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$unFinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

			$taskIdsToComplete = [];
			foreach ($finishedTaskIds as $key => $finishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($finishedTaskId);
				if ($taskService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $taskService->getErrors());
					return null;
				}

				if (!$isCompletedTask)
				{
					$taskIdsToComplete[] = $finishedTaskId;
				}
			}

			$taskService->completeTasks($taskIdsToComplete);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $taskService->getErrors());
				return null;
			}

			foreach ($unFinishedTaskIds as $key => $unFinishedTaskId)
			{
				$isCompletedTask = $taskService->isCompletedTask($unFinishedTaskId);
				if ($taskService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $taskService->getErrors());
					return null;
				}
				if ($isCompletedTask)
				{
					$kanbanService->addTaskToFinishStatus($sprint->getId(), $unFinishedTaskId);
					unset($unFinishedTaskIds[$key]);
				}
			}
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $kanbanService->getErrors());
				return null;
			}

			if ($isTargetBacklog)
			{
				$entity = $backlogService->getBacklogByGroupId($sprint->getGroupId());
			}
			else
			{
				if ($targetSprintId)
				{
					$entity = $sprintService->getSprintById($targetSprintId);
				}
				else
				{
					$group = Workgroup::getById($groupId);
					$countSprints = count($sprintService->getSprintsByGroupId($groupId));
					$entity = $this->createSprint($sprintService, [
						'groupId' => $sprint->getGroupId(),
						'tmpId' => '',
						'name' => Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => $countSprints + 1]),
						'sort' => 0,
						'userId' => $this->userId,
						'dateStart' => time(),
						'dateEnd' => time() + $group->getDefaultSprintDuration(),
					]);
				}
			}

			$itemIds = $itemService->getItemIdsBySourceIds($unFinishedTaskIds, $sprint->getId());

			if (!$itemService->getErrors() && !$sprintService->getErrors() && !$backlogService->getErrors())
			{
				$pushService = (Loader::includeModule('pull') ? new PushService() : null);
				$itemService->moveItemsToEntity($itemIds, $entity->getId(), $pushService);
			}

			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$sprintService->completeSprint($sprint, $pushService);
			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $sprintService->getErrors());
				return null;
			}

			(new CacheService($groupId, CacheService::TEAM_SPEED_CHART))->clean();

			return ['movedItemIds' => $itemIds];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), [], $exception);
			return null;
		}
	}

	public function changeTaskResponsibleAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$taskId = (is_numeric($post['sourceId']) ? (int) $post['sourceId'] : 0);
			$responsible = (is_array($post['responsible']) ? $post['responsible'] : []);
			$responsibleId = (is_numeric($responsible['id']) ? (int) $responsible['id'] : 0);

			if (!$taskId || !$responsibleId)
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_RESPONSIBLE_UPDATE_ERROR'));
				return null;
			}

			$taskService = new TaskService($this->userId, $this->application);
			$taskService->changeTask($taskId, [
				'RESPONSIBLE_ID' => $responsibleId
			]);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_RESPONSIBLE_UPDATE_ERROR'), $taskService->getErrors());
				return null;
			}
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_RESPONSIBLE_UPDATE_ERROR'), [], $exception);
			return null;
		}
	}

	public function createEpicAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$name = (is_string($post['name'] ) ? $post['name'] : 'The epic');
			$description = (is_string($post['description'] ) ? $post['description'] : '');
			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$color = (is_string($post['color'] ) ? $post['color'] : '');
			$files = (is_array($post['files']) ? $post['files'] : []);

			$epic = ItemTable::createItemObject();
			$epic->setItemType(ItemTable::EPIC_TYPE);
			$epic->setEntityId($entityId);
			$epic->setName($name);
			$epic->setDescription($description);
			$epic->setCreatedBy($this->userId);

			$itemInfo = new ItemInfoColumn();
			$itemInfo->setColor($color);
			$epic->setInfo($itemInfo);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$epic = $itemService->createEpicItem($epic, $pushService);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ADD_ERROR'), $itemService->getErrors());
				return null;
			}

			if ($files)
			{
				$itemService->attachFilesToItem($this->userFieldManager, $epic->getId(), $files);
				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ADD_ERROR'), $itemService->getErrors());
					return null;
				}
			}

			return $itemService->getEpicInfo($epic->getId());
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ADD_ERROR'), [], $exception);
			return null;
		}
	}

	public function getEpicAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (is_numeric($post['id']) ? (int) $post['id'] : 0);

			$itemService = new ItemService();

			return $itemService->getEpicInfo($epicId);
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_GET_ERROR'), [], $exception);
			return null;
		}
	}

	public function editEpicAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$name = (is_string($post['name'] ) ? $post['name'] : 'The epic');
			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
			$description = (is_string($post['description'] ) ? $post['description'] : '');
			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$color = (is_string($post['color'] ) ? $post['color'] : '');
			$files = (is_array($post['files']) ? $post['files'] : []);

			$epic = ItemTable::createItemObject();
			$epic->setId($epicId);
			$epic->setItemType(ItemTable::EPIC_TYPE);
			$epic->setEntityId($entityId);
			$epic->setName($name);
			$epic->setDescription($description);
			$epic->setModifiedBy($this->userId);

			$itemInfo = new ItemInfoColumn();
			$itemInfo->setColor($color);
			$epic->setInfo($itemInfo);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$itemService->changeItem($epic, $pushService);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_EDIT_ERROR'), $itemService->getErrors());
				return null;
			}

			if ($files)
			{
				$itemService->attachFilesToItem($this->userFieldManager, $epic->getId(), $files);
				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_EDIT_ERROR'), $itemService->getErrors());
					return null;
				}
			}

			return $itemService->getEpicInfo($epic->getId());
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_EDIT_ERROR'), [], $exception);
			return null;
		}
	}

	public function createTypeAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$name = (is_string($post['name']) ? $post['name'] : '');
			$sort = (is_numeric($post['sort']) ? (int) $post['sort'] : 0);

			$typeService = new TypeService();

			$type = $typeService->getType();
			$type->setEntityId($entityId);
			$type->setName($name);
			$type->setSort($sort);

			$createdType = $typeService->createType($type);

			if ($typeService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $typeService->getErrors());

				return null;
			}

			return $typeService->getTypeData($createdType);
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function changeTypeNameAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$id = (is_numeric($post['id']) ? (int) $post['id'] : 0);
			$name = (is_string($post['name']) ? $post['name'] : '');

			$typeService = new TypeService();

			$type = $typeService->getType();
			$type->setId($id);
			$type->setName($name);

			$typeService->changeType($type);

			if ($typeService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $typeService->getErrors());

				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function removeTypeAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$id = (is_numeric($post['id']) ? (int) $post['id'] : 0);

			$typeService = new TypeService();
			$definitionOfDoneService = new DefinitionOfDoneService($this->userId);
			$itemService = new ItemService();

			$type = $typeService->getType();
			$type->setId($id);

			$typeService->removeType($type);

			if ($typeService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $typeService->getErrors());

				return null;
			}

			$definitionOfDoneService->removeList(TypeChecklistFacade::class, $type->getId());

			$itemIds = $itemService->getItemIdsByTypeId($type->getId());

			foreach ($itemIds as $itemId)
			{
				$definitionOfDoneService->removeList(ItemChecklistFacade::class, $itemId);
			}

			$itemService->cleanTypeIdToItems($itemIds);

			return '';
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function saveDodSettingsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$items = (is_array($post['items']) ? $post['items'] : []);
			$requiredOption = (is_string($post['requiredOption']) ? $post['requiredOption'] : 'N');

			$definitionOfDoneService = new DefinitionOfDoneService($this->userId);

			$result = $definitionOfDoneService->mergeList(TypeChecklistFacade::class, $entityId, $items);

			$result->setData(
				array_merge(($result->getData() ?? []), ['OPEN_TIME' => (new DateTime())->getTimestamp()])
			);

			$typeService = new TypeService();

			$type = $typeService->getType();
			$type->setId($entityId);
			$type->setDodRequired($requiredOption);

			$typeService->changeType($type);

			if ($result->isSuccess())
			{
				return '';
			}
			else
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
				return null;
			}
		}
		catch (\Exception $exception)
		{
			return null;
		}
	}

	public function getDodChecklistAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);

			$definitionOfDoneService = new DefinitionOfDoneService($this->userId);

			$items = $definitionOfDoneService->getTypeItems($entityId);

			return $definitionOfDoneService->getComponent($entityId, 'SCRUM_ENTITY', $items);
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getDodSettingsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);

			$typeService = new TypeService();

			$types = [];
			foreach ($typeService->getTypes($entityId) as $type)
			{
				$types[] = $typeService->getTypeData($type);
			}

			if ($typeService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $typeService->getErrors());

				return null;
			}

			return [
				'types' => $types,
			];
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getEpicsListAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$isGridRequest = ($request->get('grid_id') != null);

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$gridId = $post['gridId'];

			$nav = new PageNavigation('page');
			$nav->allowAllRecords(true)->setPageSize(10)->initFromUri();

			$itemService = new ItemService();
			$userService = new UserService();

			$columns = $this->getUiGridColumns();

			$rows = [];

			$epicsList = $itemService->getEpicsList($entityId, $this->getGridOrder($gridId), $nav);
			/** @var $epicsList ItemTable[] */
			foreach ($epicsList as $epic)
			{
				$usersInfo = $userService->getInfoAboutUsers([$epic->getCreatedBy()]);
				$rows[] = [
					'id' => $epic->getId(),
					'columns' => [
						'NAME' => $this->getEpicGridColumnName($epic),
						'TAGS' => $this->getEpicGridColumnTags($epic),
						'TASKS_TOTAL' => $this->getEpicGridColumnTasksTotal($epic),
						'TASKS_COMPLETED' => $this->getEpicGridColumnTasksCompleted($epic),
						'USER' => $this->getEpicGridColumnUser($usersInfo),
					],
					'actions' => [
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_VIEW'),
							'onclick' => 'BX.Tasks.Scrum.Entry.openEpicViewForm("'.$epic->getId().'");',
						],
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_EDIT'),
							'onclick' => 'BX.Tasks.Scrum.Entry.openEpicEditForm("'.$epic->getId().'");',
						],
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_REMOVE'),
							'onclick' => 'BX.Tasks.Scrum.Entry.removeEpic("'.$epic->getId().'");',
						]
					]
				];
			}

			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}

			if (empty($rows))
			{
				return '';
			}

			$component = new Component('bitrix:main.ui.grid', '', [
				'GRID_ID' => $gridId,
				'COLUMNS' => $columns,
				'ROWS' => $rows,
				'NAV_OBJECT' => $nav,
				'NAV_PARAMS' => ['SHOW_ALWAYS' => false],
				'SHOW_PAGINATION' => true,
				'SHOW_TOTAL_COUNTER' => true,
				'TOTAL_ROWS_COUNT' => $nav->getRecordCount(),
				'ALLOW_COLUMNS_SORT' => true,
				'ALLOW_COLUMNS_RESIZE' => false,
				'AJAX_MODE' => 'N',
				'AJAX_OPTION_JUMP' => 'N',
				'AJAX_OPTION_STYLE' => 'N',
				'AJAX_OPTION_HISTORY' => 'N'
			]);

			if ($isGridRequest)
			{
				$response = new HttpResponse();
				$content = Json::decode($component->getContent());
				$response->setContent($content['data']['html']);
				return $response;
			}

			return $component;
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getEpicDescriptionEditorAction()
	{
		try
		{
			$this->checkModules();
			if (!ModuleManager::isModuleInstalled('disk'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
			$description = ($post['text'] ? $post['text'] : '');
			$description = str_replace("\r\n", "\n", $description);

			$buttons = ['UploadImage', 'UploadFile', 'CreateLink'];

			$itemService = new ItemService();
			$userFields = $itemService->getUserFields($this->userFieldManager, $epicId);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}
			$fileField = $userFields['UF_SCRUM_ITEM_FILES'];

			$params = [
				'FORM_ID' => $post['editorId'],
				'SHOW_MORE' => 'N',
				'PARSER' => [
					'Bold', 'Italic', 'Underline', 'Strike', 'ForeColor', 'FontList', 'FontSizeList',
					'RemoveFormat', 'Quote', 'Code', 'CreateLink', 'Image', 'Table', 'Justify',
					'InsertOrderedList', 'InsertUnorderedList', 'SmileList', 'Source', 'UploadImage', 'MentionUser'
				],
				'BUTTONS' => $buttons,
				'FILES' => [
					'VALUE' => [],
					'DEL_LINK' => '',
					'SHOW' => 'N'
				],

				'TEXT' => [
					'INPUT_NAME' => 'ACTION[0][ARGUMENTS][data][DESCRIPTION]',
					'VALUE' => $description,
					'HEIGHT' => '120px'
				],
				'PROPERTIES' => [],
				'UPLOAD_FILE' => true,
				'UPLOAD_WEBDAV_ELEMENT' => $fileField ?? false,
				'UPLOAD_FILE_PARAMS' => ['width' => 400, 'height' => 400],
				'NAME_TEMPLATE' => Util\Site::getUserNameFormat(),
				'LHE' => [
					'id' => $post['editorId'],
					'iframeCss' => 'body { padding-left: 10px !important; }',
					'fontFamily' => "'Helvetica Neue', Helvetica, Arial, sans-serif",
					'fontSize' => '13px',
					'bInitByJS' => false,
					'height' => 100,
					'lazyLoad' => 'N',
					'bbCode' => true,
				]
			];

			return new Component('bitrix:main.post.form', '', $params);
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getEpicDescriptionAction()
	{
		try
		{
			$this->checkModules();
			if (!ModuleManager::isModuleInstalled('disk'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
			$description = ($post['text'] ? $post['text'] : '');
			$description = str_replace("\r\n", "\n", $description);

			$itemService = new ItemService();
			$userFields = $itemService->getUserFields($this->userFieldManager, $epicId);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}

			$taskService = new TaskService($this->userId, $this->application);
			$outDescription = $taskService->convertDescription($description, $userFields);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			return $outDescription;
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getEpicFilesAction()
	{
		try
		{
			$this->checkModules();
			if (!ModuleManager::isModuleInstalled('disk'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

			$itemService = new ItemService();
			$userFields = $itemService->getUserFields($this->userFieldManager, $epicId);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}
			$fileField = $userFields['UF_SCRUM_ITEM_FILES'];

			return new Component(
				'bitrix:system.field.view',
				$fileField['USER_TYPE']['USER_TYPE_ID'],
				[
					'arUserField' => $fileField,
				]
			);
		}
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getBurnDownChartDataAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$inputSprintId = (is_numeric($post['sprintId']) ? (int) $post['sprintId'] : 0);

			$sprintService = new SprintService();
			$sprint = $sprintService->getSprintById($inputSprintId);
			if ($sprint->isEmpty())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_GET_DATA_ERROR'));
				return null;
			}

			if ($sprint->isActiveSprint())
			{
				$currentDateTime = new Datetime();
				$currentDateEnd = $sprint->getDateEnd();
				$sprint->setDateEnd($currentDateEnd > $currentDateTime ? $currentDateEnd : $currentDateTime);
			}

			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$taskService = new TaskService($this->userId, $this->application);

			$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$uncompletedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
			$taskIds = array_merge($completedTaskIds, $uncompletedTaskIds);

			$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($taskIds);

			$storyPointsService = new StoryPoints();
			$sumStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);

			$calendar = new Util\Calendar();
			$sprintRanges = $sprintService->getSprintRanges($sprint, $calendar);

			$completedTasksMap = $sprintService->getCompletedTasksMap($sprintRanges, $taskService, $completedTaskIds);
			$completedStoryPointsMap = $sprintService->getCompletedStoryPointsMap(
				$sumStoryPoints,
				$completedTasksMap,
				$itemsStoryPoints
			);

			$burnDownChart = new BurnDownChart();
			$idealData = $burnDownChart->prepareIdealBurnDownChartData($sumStoryPoints, $sprintRanges);
			$remainingData = $burnDownChart->prepareRemainBurnDownChartData(
				$sumStoryPoints,
				$sprintRanges,
				$completedStoryPointsMap
			);

			return array_merge($idealData, $remainingData);
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function getTeamSpeedChartDataAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$groupId = $this->arParams['GROUP_ID'];

			$cacheService = new CacheService($groupId, CacheService::TEAM_SPEED_CHART);

			if ($cacheService->init())
			{
				$data = $cacheService->getData();
			}
			else
			{
				$sprintService = new SprintService();
				$itemService = new ItemService();
				$kanbanService = new KanbanService();

				$cacheService->start();

				$data = [];

				foreach ($sprintService->getCompletedSprintsByGroupId($groupId) as $sprint)
				{
					$completedPoints = $sprintService->getCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					);

					$uncompletedPoints = $sprintService->getUnCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					);

					$data[] = [
						'sprintName' => $sprint->getName(),
						'plan' => round(($completedPoints + $uncompletedPoints), 2),
						'done' => $completedPoints,
					];
				}

				$cacheService->end($data);
			}

			return $data;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function getAddEpicFormButtonsAction()
	{
		try
		{
			if (!ModuleManager::isModuleInstalled('ui'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			return new Component('bitrix:ui.button.panel', '', [
				'FRAME' => true,
				'BUTTONS' => [
					'save',
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

	public function getViewEpicFormButtonsAction()
	{
		try
		{
			if (!ModuleManager::isModuleInstalled('ui'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			return new Component('bitrix:ui.button.panel', '', [
				'FRAME' => true,
				'BUTTONS' => [
					[
						'ID' => 'epic_edit',
						'TYPE' => 'save',
						'CAPTION' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_EDIT')
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

	public function getSprintStartButtonsAction()
	{
		try
		{
			if (!ModuleManager::isModuleInstalled('ui'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			return new Component('bitrix:ui.button.panel', '', [
				'FRAME' => true,
				'BUTTONS' => [
					[
						'ID' => 'sprint_start',
						'TYPE' => 'save',
						'CAPTION' => Loc::getMessage('TASKS_SCRUM_SPRINT_START_BUTTON')
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

	public function getSprintCompleteButtonsAction()
	{
		try
		{
			if (!ModuleManager::isModuleInstalled('ui'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}

			return new Component('bitrix:ui.button.panel', '', [
				'FRAME' => true,
				'BUTTONS' => [
					[
						'ID' => 'sprint_complete',
						'TYPE' => 'save',
						'CAPTION' => Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_BUTTON')
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

	public function getAllUsedItemBorderColorsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$entityIds = (is_array($post['entityIds']) ? $post['entityIds'] : []);

			$itemService = new ItemService();

			$allUsedItemBorderColors = [];
			foreach ($entityIds as $entityId)
			{
				$entityId = (is_numeric($entityId) ? (int)$entityId : 0);

				foreach ($itemService->getTaskItemsByEntityId($entityId) as $item)
				{
					$allUsedItemBorderColors[] = $item->getInfo()->getBorderColor();
				}
			}

			return array_values(array_filter(array_unique($allUsedItemBorderColors)));
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	public function updateBorderColorToLinkedItemsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();

			$items = (is_array($post['items']) ? $post['items'] : []);

			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$taskService = new TaskService($this->userId);

			$itemsToUpdateBorderColor = [];
			$itemObjectsMap = [];
			$itemSourceIdsMap = [];
			$itemLinkedTasksMap = [];
			foreach ($items as $itemId => $randomColor)
			{
				$itemId = (is_numeric($itemId) ? (int)$itemId : 0);

				$item = $itemService->getItemById($itemId);
				if ($item->isEmpty())
				{
					continue;
				}

				$itemObjectsMap[$itemId] = $item;

				$taskId = $item->getSourceId();
				$linkedTaskIds = $taskService->getLinkedTasks($taskId);
				$itemsToUpdateBorderColor[$itemId] = $this->getBorderColorByLinkedTasks(
					$itemService,
					$linkedTaskIds,
					$randomColor
				);

				$itemSourceIdsMap[$itemId] = $taskId;
				$itemLinkedTasksMap[$itemId] = $linkedTaskIds;
			}

			$updatedItems = [];

			if ($itemsToUpdateBorderColor)
			{
				$colorMap = $this->getColorMapForItemsRelatedToEachOther(
					$itemsToUpdateBorderColor,
					$itemSourceIdsMap,
					$itemLinkedTasksMap
				);

				foreach ($itemsToUpdateBorderColor as $itemId => $borderColor)
				{
					if (isset($itemObjectsMap[$itemId]))
					{
						$itemObject = $itemObjectsMap[$itemId];
						$infoBorderColor = (array_key_exists($itemId, $colorMap) ? $colorMap[$itemId] : $borderColor);
						$info = $itemObject->getInfo();
						$info->setBorderColor($infoBorderColor);
						$itemObject->setInfo($info);
						if ($itemService->changeItem($itemObject, $pushService))
						{
							$updatedItems[$itemId] = $infoBorderColor;
						}
					}
				}
			}

			return $updatedItems;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	public function getSubTaskItemsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = (int)Util\User::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$entityId = (is_numeric($post['entityId']) ? (int)$post['entityId'] : 0);
			$taskId = (is_numeric($post['taskId']) ? (int)$post['taskId'] : 0);

			$entityService = new EntityService();
			$kanbanService = new KanbanService();
			$taskService = new TaskService($this->userId);

			$entity = $entityService->getEntityById($entityId);

			if ($entity->isActiveSprint())
			{
				$subTaskIds = $taskService->getSubTaskIds($groupId, $taskId, false);

				foreach ($subTaskIds as $key => $subTaskId)
				{
					if (!$kanbanService->isTaskInKanban($entity->getId(), $subTaskId))
					{
						unset($subTaskIds[$key]);
					}
				}
			}
			else
			{
				$subTaskIds = $taskService->getSubTaskIds($groupId, $taskId);
			}

			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			if (empty($subTaskIds))
			{
				return [];
			}

			$itemService = new ItemService();
			$userService = new UserService();

			$items = [];
			$itemIds = $itemService->getItemIdsBySourceIds($subTaskIds);
			$itemIds = array_reverse($itemIds, true);
			foreach ($itemIds as $itemId)
			{
				$item = $itemService->getItemById($itemId);
				if (!$itemService->getErrors() && !$item->isEmpty())
				{
					$items[] = $item;
				}
			}

			$itemsData = [];
			foreach ($items as $item)
			{
				$item->setEntityId($entityId);
				$itemsData[] = $this->getItemsData([$item->getId()], $itemService, $taskService, $userService)[0];
			}

			return $itemsData;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	public function getItemsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$entityId = (is_numeric($post['entityId']) ? (int) $post['entityId'] : 0);
			$pageNumber = (is_numeric($post['pageNumber']) ? (int) $post['pageNumber'] : 1);

			$itemService = new ItemService();
			$userService = new UserService();
			$taskService = new TaskService($this->userId, $this->application);
			$taskService->setOwnerId($this->arParams['OWNER_ID']);

			$filterInstance = $taskService->getFilterInstance($groupId);
			$filter = $taskService->getFilter($filterInstance);
			$epicTaskIds = $this->getEpicTaskIdsFromFilter($filter);
			$taskIds = $taskService->getTaskIdsByFilter($filter);
			if ($epicTaskIds)
			{
				$taskIds = array_intersect($epicTaskIds, $taskIds);
			}
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			$nav = $this->getNavToItems($pageNumber);

			$itemIds = $itemService->getItemIdsBySourceIds($taskIds, $entityId, $nav);

			return $this->getItemsData($itemIds, $itemService, $taskService, $userService);
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	public function getEntityCountersAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$entityIds = (is_array($post['entityIds']) ? $post['entityIds'] : []);

			$entityService = new EntityService();
			$sprintService = new SprintService();
			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$taskService = new TaskService($this->userId);

			$entitiesCounters = [];

			foreach ($entityIds as $entityId)
			{
				$entity = $entityService->getEntityById($entityId);

				$completedStoryPoints = '';
				$uncompletedStoryPoints = '';

				if ($entity->isActiveSprint())
				{
					$completedStoryPoints = $sprintService->getCompletedStoryPoints(
						$entity,
						$kanbanService,
						$itemService
					);
					$uncompletedStoryPoints = $sprintService->getUnCompletedStoryPoints(
						$entity,
						$kanbanService,
						$itemService
					);

					$entityCounters = $entityService->getCounters($entity->getId());
				}
				else if ($entity->isPlannedSprint())
				{
					$entityCounters = $entityService->getCounters($entity->getId(), $taskService);
				}
				else if ($entity->isCompletedSprint())
				{
					$entityCounters = $entityService->getCounters($entity->getId());
				}
				else
				{
					$entityCounters = $entityService->getCounters($entity->getId(), $taskService);
				}

				$entitiesCounters[$entityId] = [
					'storyPoints' => $entityCounters['storyPoints'],
					'completedStoryPoints' => $completedStoryPoints,
					'uncompletedStoryPoints' => $uncompletedStoryPoints,
					'numberTasks' => $entityCounters['countTotal'],
				];
			}

			return $entitiesCounters;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	public function getCompletedSprintsAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$pageNumber = (is_numeric($post['pageNumber']) ? (int) $post['pageNumber'] : 1);

			$nav = $this->getNavToCompletedSprints($pageNumber);

			$taskService = new TaskService($this->userId, $this->application);

			$filterInstance = $taskService->getFilterInstance($groupId);
			if ($filterInstance && $filterInstance->isSearchFieldApplied())
			{
				return [];
			}

			$entityService = new EntityService();
			$sprintService = new SprintService();
			$itemService = new ItemService();
			$kanbanService = new KanbanService();

			$completedSprints = $sprintService->getCompletedSprints($groupId, $nav, $itemService, $filterInstance);
			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $sprintService->getErrors());

				return null;
			}

			$sprints = [];

			$sprintViews = $this->getViewsInfo($groupId);

			foreach ($completedSprints as $sprint)
			{
				$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

				$sprints[] = $this->prepareSprintData(
					$sprint,
					$sprintViews,
					$cacheService,
					$entityService,
					$sprintService,
					$itemService,
					$kanbanService,
					$filterInstance
				);
			}

			return $sprints;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);

			return null;
		}
	}

	/**
	 * @throws SystemException
	 */
	private function checkModules()
	{
		try
		{
			if (!Loader::includeModule('tasks'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}
			if (!Loader::includeModule('socialnetwork'))
			{
				throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
			}
		}
		catch (LoaderException $exception)
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
		}
	}

	private function canReadGroupTasks(int $groupId): bool
	{
		return Group::canReadGroupTasks($this->userId, $groupId);
	}

	private function includePlanTemplate(int $groupId): void
	{
		$taskService = new TaskService($this->userId, $this->application);

		$filterInstance = $taskService->getFilterInstance($groupId, false);

		$this->arResult['filterInstance'] = $filterInstance;

		$userService = new UserService();

		$this->arResult['tags'] = [];
		$this->arResult['tags']['task'] = $taskService->getTagsByUserIds([$this->userId]);

		$this->arResult['activeSprintId'] = 0;

		$responsibleId = $this->getDefaultResponsibleId($groupId);
		$this->arResult['defaultResponsible'] = $userService->getInfoAboutUsers([$responsibleId]);

		$this->arResult['counters'] = null;
		if ($taskService->hasAccessToCounters())
		{
			$this->arResult['counters'] = $this->getCounters($this->userId, $groupId, $filterInstance);
		}

		$itemsNav = $this->getNavToItems();
		$completedSprintNav = $this->getNavToCompletedSprints();

		$filter = $taskService->getFilter($filterInstance);

		$taskIds = $taskService->getTaskIdsByFilter($filter);
		$epicTaskIds = $this->getEpicTaskIdsFromFilter($filter);
		if ($epicTaskIds)
		{
			$taskIds = array_intersect($epicTaskIds, $taskIds);
		}
		$this->filteredTaskIds = $taskIds;

		$itemService = new ItemService();

		if ($this->getErrors())
		{
			$this->includeErrorTemplate(current($this->getErrors()), $this->getFirstErrorCode($this->getErrors()));

			return;
		}

		$entityService = new EntityService();

		$backlog = $this->getBacklog($groupId, $itemsNav);
		if ($backlog->isEmpty())
		{
			$backlog = $this->createNewBacklogForThisProject($groupId);
		}

		$this->syncItemsWithTasks($backlog->getId(), $this->userId, $groupId, $this->filteredTaskIds);

		$this->arResult['sprints'] = $this->getSprints($groupId, $itemsNav, $completedSprintNav, $filterInstance);

		$group = Workgroup::getById($groupId);

		$this->arResult['defaultSprintDuration'] = $group->getDefaultSprintDuration();

		$this->arResult['tags']['epic'] = $itemService->getAllEpicTags($backlog->getId());

		$backlogItems = $this->prepareEntityItems($backlog);

		$entityCounters = $entityService->getCounters($backlog->getId(), $taskService);

		$this->arResult['backlog'] = [
			'id' => $backlog->getId(),
			'storyPoints' => $entityCounters['storyPoints'],
			'numberTasks' => $entityCounters['countTotal'],
			'items' => $backlogItems,
			'isExactSearchApplied' => ($filterInstance->isSearchFieldApplied() ? 'Y' : 'N'),
			'pageNumberItems' => 1,
		];

		$info = $backlog->getInfo();

		$typeService = new TypeService();

		if ($typeService->isEmpty($backlog->getId()) && !$info->isTypesGenerated())
		{
			$productType = $typeService->getType();
			$productType->setEntityId($backlog->getId());
			$productType->setName(Loc::getMessage('TASKS_SCRUM_TYPE_PRODUCT_NAME'));
			$productType->setSort(1);
			$productType->setDodRequired('Y');

			$technicalType = $typeService->getType();
			$technicalType->setEntityId($backlog->getId());
			$technicalType->setName(Loc::getMessage('TASKS_SCRUM_TYPE_TECHNICAL_NAME'));
			$technicalType->setSort(2);

			$typeService->createType($productType);
			$typeService->createType($technicalType);

			if (!$typeService->getErrors())
			{
				$definitionOfDoneService = new DefinitionOfDoneService($responsibleId);

				if ($definitionOfDoneService->isTypeListEmpty($productType->getId()))
				{
					$definitionOfDoneService->createDefaultList($productType->getId());
				}

				$this->updateTypeCreationStatus($backlog);
			}
		}

		if ($this->getErrors())
		{
			$this->includeErrorTemplate(current($this->getErrors()), $this->getFirstErrorCode($this->getErrors()));

			return;
		}

		$this->includeComponentTemplate('plan');
	}

	private function includeActiveSprintTemplate(int $groupId): void
	{
		$taskService = new TaskService($this->userId, $this->application);
		$filterInstance = $taskService->getFilterInstance($groupId, false);

		$entityService = new EntityService();
		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$this->arResult['filterInstance'] = $filterInstance;

		$this->arResult['tags'] = [];
		$this->arResult['tags']['task'] = [];

		$filter = $taskService->getFilter($filterInstance);

		$filter['ONLY_ROOT_TASKS'] = 'N';

		$taskIds = $taskService->getTaskIdsByFilter($filter);
		$epicTaskIds = $this->getEpicTaskIdsFromFilter($filter);
		if ($epicTaskIds)
		{
			$taskIds = array_intersect($epicTaskIds, $taskIds);
		}
		$this->filteredTaskIds = $taskIds;

		$sprint = $sprintService->getActiveSprintByGroupId($groupId, $itemService);

		if ($sprint->isActiveSprint())
		{
			$this->arResult['activeSprintId'] = ($sprintService->getErrors() ? 0 : $sprint->getId());

			$this->arResult['taskLimitExceeded'] = Bitrix24Restriction\Limit\TaskLimit::isLimitExceeded();
			$this->arResult['canUseAutomation'] = Factory::canUseAutomation();

			$this->arResult['orderNewTask'] = $kanbanService->getKanbanSortValue($groupId);

			$sprintData = $sprintService->getSprintData($sprint);

			$entityCounters = $entityService->getCounters($sprint->getId());

			$sprintData['items'] = $this->prepareEntityItems($sprint);

			$sprintData['numberTasks'] = $entityCounters['countTotal'];

			$sprintData['storyPoints'] = $entityCounters['storyPoints'];
			$sprintData['completedStoryPoints'] = $sprintService->getCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);
			$sprintData['uncompletedStoryPoints'] = $sprintService->getUnCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);

			$sprintData['completedTasks'] = count($kanbanService->getFinishedTaskIdsInSprint($sprint->getId()));
			$sprintData['uncompletedTasks'] = count($kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId()));
			$sprintData['finishStatus'] = $kanbanService->getFinishStatus();
			$sprintData['isExactSearchApplied'] = ($filterInstance->isSearchFieldApplied() ? 'Y' : 'N');

			$this->arResult['activeSprintData'] = $sprintData;
		}

		if ($this->getErrors())
		{
			$this->includeErrorTemplate(current($this->getErrors()), $this->getFirstErrorCode($this->getErrors()));

			return;
		}

		$uncompletedSprints = $sprintService->getUncompletedSprints($groupId);

		$lastCompletedSprint = $sprintService->getLastCompletedSprint($groupId);
		$lastCompletedSprint->setChildren($itemService->getHierarchyChildItems($lastCompletedSprint));

		$completedSprints = $sprintService->getCompletedSprints($groupId);
		foreach ($completedSprints as $key => $completedSprint)
		{
			if ($completedSprint->getId() === $lastCompletedSprint->getId())
			{
				$completedSprints[$key] = $lastCompletedSprint;
			}
		}

		$listSprints = array_merge($uncompletedSprints, $completedSprints);

		$sprints = [];
		foreach ($listSprints as $sprint)
		{
			$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

			$sprints[] = $this->prepareSprintData(
				$sprint,
				$this->arResult['views'],
				$cacheService,
				$entityService,
				$sprintService,
				$itemService,
				$kanbanService,
				$filterInstance
			);
		}

		$this->arResult['sprints'] = $sprints;

		$this->includeComponentTemplate('active_sprint');
	}

	private function includeCompletedSprintTemplate(int $groupId, int $sprintId): void
	{
		$taskService = new TaskService($this->userId, $this->application);
		$filterInstance = $taskService->getFilterInstance($groupId, true);

		$this->arResult['filterInstance'] = $filterInstance;

		$this->arResult['tags'] = [];
		$this->arResult['tags']['task'] = [];

		$entityService = new EntityService();
		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		if ($sprintId)
		{
			$completedSprint = $sprintService->getSprintById($sprintId);
		}
		else
		{
			$completedSprint = $sprintService->getLastCompletedSprint($groupId);
		}

		$completedSprintData = $sprintService->getSprintData($completedSprint);

		$sprintViews = $this->arResult['views'];
		$sprintViews['completedSprint']['url'] = $sprintViews['completedSprint']['url']
			. '&sprintId=' . $completedSprint->getId();
		$completedSprintData['views'] = $sprintViews;

		$sprints = [];
		foreach ($sprintService->getCompletedSprints($groupId) as $sprint)
		{
			$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

			$sprints[] = $this->prepareSprintData(
				$sprint,
				$sprintViews,
				$cacheService,
				$entityService,
				$sprintService,
				$itemService,
				$kanbanService
			);
		}

		$this->arResult['sprints'] = $sprints;
		$this->arResult['completedSprintId'] = $completedSprint->getId();
		$this->arResult['completedSprint'] = $completedSprintData;

		$this->includeComponentTemplate('completed_sprint');
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}

	private function init()
	{
		$currentUserId = (int)Util\User::getId();

		$this->arParams['USER_ID'] = $this->userId = $currentUserId;

		$this->arResult['isOwnerCurrentUser'] = ($currentUserId === $this->arParams['OWNER_ID']);
	}

	private function setTitle()
	{
		if ($this->arParams['SET_TITLE'])
		{
			$title = Loc::getMessage('TASKS_SCRUM_TITLE_BASE');
			$this->application->setTitle($title);

			if (
				(int)$this->arParams['GROUP_ID'] > 0
				&& \Bitrix\Main\Loader::includeModule('socialnetwork')
				&& method_exists(Bitrix\Socialnetwork\ComponentHelper::class, 'getWorkgroupPageTitle')
			)
			{
				$this->application->SetPageProperty('title', \Bitrix\Socialnetwork\ComponentHelper::getWorkgroupPageTitle([
					'WORKGROUP_ID' => (int)$this->arParams['GROUP_ID'],
					'TITLE' => $title
				]));
			}
		}
	}

	/**
	 * @param int $groupId
	 * @return EntityTable
	 * @throws SystemException
	 */
	private function getBacklog(int $groupId, PageNavigation $nav): EntityTable
	{
		$backlogService = new BacklogService();
		$itemService = new ItemService();

		$backlog = $backlogService->getBacklogByGroupId($groupId, $itemService, $nav, $this->filteredTaskIds);

		if ($backlogService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
		}

		return $backlog;
	}

	/**
	 * @param int $groupId
	 * @return EntityTable
	 * @throws SystemException
	 */
	private function createNewBacklogForThisProject(int $groupId): EntityTable
	{
		$backlogService = new BacklogService();

		$backlog = EntityTable::createEntityObject();
		$backlog->setGroupId($groupId);
		$backlog->setCreatedBy($this->userId);

		$backlog = $backlogService->createBacklog($backlog);

		if ($backlogService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_ADD_BACKLOG'));
		}

		return $backlog;
	}

	private function updateTypeCreationStatus(EntityTable $backlog): void
	{
		$backlogService = new BacklogService();

		$info = $backlog->getInfo();
		$info->setTypesGenerated('Y');

		$backlog->setInfo($info);

		$backlogService->changeBacklog($backlog);
	}

	/**
	 * @param int $groupId
	 * @param PageNavigation|null $itemNav If you need to item navigation.
	 * @param PageNavigation|null $sprintNav If you need to sprint navigation.
	 * @param Filter|null $filterInstance
	 * @return array EntityTable[]
	 * @throws SystemException
	 */
	private function getSprints(
		int $groupId,
		PageNavigation $itemNav = null,
		PageNavigation $sprintNav = null,
		$filterInstance = null
	): array
	{
		$entityService = new EntityService();
		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$uncompletedSprints = $sprintService->getUncompletedSprints(
			$groupId,
			$itemNav,
			$itemService,
			$this->filteredTaskIds
		);
		$completedSprints = $sprintService->getCompletedSprints(
			$groupId,
			$sprintNav,
			$itemService,
			$filterInstance
		);

		$listSprints = array_merge($uncompletedSprints, $completedSprints);

		if ($sprintService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
		}

		$sprints = [];

		$sprintViews = $this->getViewsInfo($groupId);

		foreach ($listSprints as $sprint)
		{
			$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

			$sprints[] = $this->prepareSprintData(
				$sprint,
				$sprintViews,
				$cacheService,
				$entityService,
				$sprintService,
				$itemService,
				$kanbanService,
				$filterInstance
			);
		}

		return $sprints;
	}

	private function prepareSprintData(
		EntityTable $sprint,
		array $sprintViews,
		CacheService $cacheService,
		EntityService $entityService,
		SprintService $sprintService,
		ItemService $itemService,
		KanbanService $kanbanService,
		$filterInstance = null
	): array
	{
		if ($sprint->isCompletedSprint() && (!$filterInstance || !$filterInstance->isSearchFieldApplied()))
		{
			if ($cacheService->init())
			{
				$sprintData = $cacheService->getData();
				$sprintViews['completedSprint']['url'] = $sprintViews['completedSprint']['url']
					. '&sprintId=' . $sprint->getId();
				$sprintData['views'] = $sprintViews;

				return $sprintData;
			}
		}

		$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
		$unfinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());

		$completedTasks = count($finishedTaskIds);
		$uncompletedTasks = count($unfinishedTaskIds);

		$sprintData = $sprintService->getSprintData($sprint);

		if ($sprint->isCompletedSprint())
		{
			$sprintViews['completedSprint']['url'] = $sprintViews['completedSprint']['url']
				. '&sprintId=' . $sprint->getId();
			$sprintData['views'] = $sprintViews;

			$allTaskIds = array_merge($finishedTaskIds, $unfinishedTaskIds);

			$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($allTaskIds);

			$storyPointsService = new StoryPoints();
			$sumStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);

			$entityCounters = [
				'storyPoints' => $sumStoryPoints,
				'countTotal' => count($allTaskIds),
			];
		}
		else
		{
			$entityCounters = $entityService->getCounters(
				$sprint->getId(),
				($sprint->isPlannedSprint() ? new TaskService($this->userId) : null)
			);
		}

		$sprintData['items'] = $this->prepareEntityItems($sprint);

		$sprintData['numberTasks'] = $entityCounters['countTotal'];

		$sprintData['storyPoints'] = $entityCounters['storyPoints'];
		$sprintData['completedStoryPoints'] = $sprintService->getCompletedStoryPoints(
			$sprint,
			$kanbanService,
			$itemService
		);
		$sprintData['uncompletedStoryPoints'] = $sprintService->getUnCompletedStoryPoints(
			$sprint,
			$kanbanService,
			$itemService
		);

		$sprintData['completedTasks'] = $completedTasks;
		$sprintData['uncompletedTasks'] = $uncompletedTasks;
		$sprintData['isExactSearchApplied'] = ($filterInstance && $filterInstance->isSearchFieldApplied() ? 'Y' : 'N');
		$sprintData['pageNumberItems'] = 1;

		if ($sprint->isCompletedSprint() && ($filterInstance && !$filterInstance->isSearchFieldApplied()))
		{
			$cacheService->start();
			$cacheService->end($sprintData);
		}

		return $sprintData;
	}

	private function prepareEntityItems(EntityTable $entity): array
	{
		$taskService = new TaskService($this->userId, $this->application);
		$taskService->setOwnerId($this->arParams['OWNER_ID']);
		$userService = new UserService();
		$itemService = new ItemService();

		$listItems = $this->makeListItems(
			$entity->getChildren(),
			$itemService,
			$taskService,
			$userService
		);

		//todo ppc
		if (!is_array($this->arResult['tags']['task']))
		{
			$this->arResult['tags']['task'] = [];
		}
		$this->arResult['tags']['task'] = array_merge($this->arResult['tags']['task'], $taskService->getTasksTags());

		return $listItems;
	}

	private function makeListItems(
		array $items,
		ItemService $itemService,
		TaskService $taskService,
		UserService $userService
	): array
	{
		$filteredItemIds = [];

		/**
		 * @var $items ItemTable[]
		 */
		foreach ($items as $item)
		{
			switch ($item->getItemType())
			{
				case ItemTable::TASK_TYPE:
					$taskId = $item->getSourceId();
					if (in_array($taskId, $this->filteredTaskIds))
					{
						$filteredItemIds[] = $item->getId();
					}
					break;
			}
		}

		return $this->getItemsData($filteredItemIds, $itemService, $taskService, $userService);
	}

	private function getViewsInfo(int $groupId): array
	{
		$request = Context::getCurrent()->getRequest();

		if ($request->isAjaxRequest())
		{
			$uri = new Uri(str_replace('#group_id#', $groupId, $this->arParams['PATH_TO_GROUP_TASKS']));
		}
		else
		{
			$uri = new Uri($request->getRequestUri());
		}

		$uri->deleteParams(['sprintId']);

		$uri->addParams(['tab' => 'plan']);
		$planningUrl = $uri->getUri();

		$uri->addParams(['tab' => 'active_sprint']);
		$activeSprintUrl = $uri->getUri();

		$uri->addParams(['tab' => 'completed_sprint']);
		$completedSprintUrl = $uri->getUri();

		return [
			'plan' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_PLAN'),
				'url' => $planningUrl,
				'active' => ($this->getActiveTab($groupId) == 'plan')
			],
			'activeSprint' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_SPRINT'),
				'url' => $activeSprintUrl,
				'active' => ($this->getActiveTab($groupId) == 'active_sprint')
			],
			'completedSprint' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_COMPLETED_SPRINT'),
				'url' => $completedSprintUrl,
				'active' => ($this->getActiveTab($groupId) == 'completed_sprint')
			],
		];
	}

	private function saveActiveTab()
	{
		$request = Context::getCurrent()->getRequest();

		if ($request->get('tab') == 'plan')
		{
			CUserOptions::setOption('tasks.scrum.'.$this->arParams['GROUP_ID'], 'active_tab', 'plan');
		}

		if ($request->get('tab') == 'active_sprint')
		{
			CUserOptions::setOption('tasks.scrum.'.$this->arParams['GROUP_ID'], 'active_tab', 'active_sprint');
		}

		if ($request->get('tab') == 'completed_sprint')
		{
			CUserOptions::setOption('tasks.scrum.'.$this->arParams['GROUP_ID'], 'active_tab', 'completed_sprint');
		}
	}

	private function getActiveTab(int $groupId)
	{
		return CUserOptions::getOption('tasks.scrum.'.$groupId, 'active_tab', 'plan');
	}

	private function createSprint(SprintService $sprintService, array $fields): EntityTable
	{
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = EntityTable::createEntityObject();
		$sprint->setGroupId($fields['groupId']);
		$sprint->setTmpId($fields['tmpId']);
		$sprint->setName($fields['name']);
		$sprint->setSort($fields['sort']);
		$sprint->setCreatedBy($fields['userId']);
		$sprint->setModifiedBy($fields['userId']);
		$sprint->setDateStart(DateTime::createFromTimestamp($fields['dateStart']));
		$sprint->setDateEnd(DateTime::createFromTimestamp($fields['dateEnd']));

		return $sprintService->createSprint($sprint, $pushService);
	}

	private function moveTo(
		TaskService $taskService,
		ItemService $itemService,
		KanbanService $kanbanService,
		array $itemData
	): void
	{
		$itemId = (is_numeric($itemData['itemId']) ? (int)$itemData['itemId'] : 0);
		$taskId = $itemService->getTaskIdByItemId($itemId);

		if ($this->isMoveToAnotherEntity($itemData)) // todo
		{
			$entityId = (is_numeric($itemData['entityId']) ? (int)$itemData['entityId'] : 0);

			if ($taskId)
			{
				$subTaskIds = $taskService->getSubTaskIds($this->arParams['GROUP_ID'], $taskId);

				$idsToMove = array_merge([$taskId], $subTaskIds);
				$itemIds = $itemService->getItemIdsBySourceIds($idsToMove);
				$itemService->updateEntityIdToItems($entityId, $itemIds);

				if ($this->isTaskMoveToActiveSprint($itemData))
				{
					$kanbanService->addTasksToKanban($itemData['entityId'], [$taskId]);
					$kanbanService->addTasksToKanban($itemData['entityId'], $subTaskIds);
				}

				if ($this->isTaskMoveFromActiveSprint($itemData))
				{
					$sourceEntityId = (
						is_numeric($itemData['sourceEntityId']) ? (int)$itemData['sourceEntityId'] : 0
					);
					$kanbanService->removeTasksFromKanban($sourceEntityId, $idsToMove);
				}
			}
		}
	}

	private function isMoveToAnotherEntity(array $itemData): bool
	{
		return isset($itemData['entityId']);
	}

	private function isTaskMoveToActiveSprint(array $itemData): bool
	{
		return (
			$itemData['itemType'] == ItemTable::TASK_TYPE &&
			isset($itemData['toActiveSprint']) &&
			$itemData['toActiveSprint'] == 'Y'
		);
	}

	private function isTaskMoveFromActiveSprint(array $itemData): bool
	{
		return (
			$itemData['itemType'] == ItemTable::TASK_TYPE &&
			isset($itemData['fromActiveSprint']) &&
			$itemData['fromActiveSprint'] == 'Y'
		);
	}

	private function getGridOrder(string $gridId): array
	{
		$defaultSort = ['ID' => 'DESC'];

		$gridOptions = new Grid\Options($gridId);
		$sorting = $gridOptions->getSorting(['sort' => $defaultSort]);

		$by = key($sorting['sort']);
		$order = strtoupper(current($sorting['sort'])) === 'ASC' ? 'ASC' : 'DESC';

		$list = [];
		foreach ($this->getUiGridColumns() as $column)
		{
			if (!empty($column['sort']))
			{
				$list[] = $column['sort'];
			}
		}

		if (!in_array($by, $list))
		{
			return $defaultSort;
		}

		return [$by => $order];
	}

	private function getUiGridColumns(): array
	{
		return [
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_NAME_SHORT'),
				'default' => true,
				'sort' => 'NAME',
			],
			[
				'id' => 'TAGS',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TAGS'),
				'default' => true
			],
			[
				'id' => 'TASKS_TOTAL',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TASKS_TOTAL'),
				'default' => true
			],
			[
				'id' => 'TASKS_COMPLETED',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TASKS_COMPLETED'),
				'default' => true
			],
			[
				'id' => 'USER',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_USER_SHORT'),
				'default' => true
			]
		];
	}

	private function getEpicGridColumnName(ItemTable $epic): string
	{
		$info = $epic->getInfo();
		$color = HtmlFilter::encode($info->getColor());
		$name = HtmlFilter::encode($epic->getName());
		return '
			<div class="tasks-scrum-epic-grid-name">
				<div class="tasks-scrum-epic-grid-name-color" style="background-color: '.$color.';"></div>
				<a onclick="BX.Tasks.Scrum.Entry.openEpicViewForm(\''.$epic->getId().'\')" class=
					"tasks-scrum-epic-name-label">'.$name.'</a>
			</div>
		';
	}

	private function getEpicGridColumnTags(ItemTable $epic): string
	{
		$itemService = new ItemService();
		$taskService = new TaskService($this->userId, $this->application);

		$taskIds = $itemService->getTaskIdsByParentId($epic->getId());
		$tags = $taskService->getTagsByTaskIds($taskIds);

		$tagsNodes = [];
		foreach ($tags as $tagName)
		{
			$tagsNodes[] = '<div>'.HtmlFilter::encode($tagName).'</div>';
		}

		return '<div class="tasks-scrum-epic-grid-tags">'.implode('', $tagsNodes).'</div>';
	}

	private function getEpicGridColumnTasksTotal(ItemTable $epic): string
	{
		$itemService = new ItemService();

		return '
			<div class="tasks-scrum-epic-grid-tasks-total">
				'.count($itemService->getTaskIdsByParentId($epic->getId())).'
			</div>
		';
	}

	private function getEpicGridColumnTasksCompleted(ItemTable $epic): string
	{
		$itemService = new ItemService();

		$kanbanService = new KanbanService();
		$finishedTaskIds = $kanbanService->extractFinishedTaskIds($itemService->getTaskIdsByParentId($epic->getId()));

		return '
			<div class="tasks-scrum-epic-grid-tasks-completed">
				'.count($finishedTaskIds).'
			</div>
		';
	}

	private function getEpicGridColumnUser(array $usersInfo): string
	{
		return '
			<div>
				<a href="'.$usersInfo['pathToUser'].'">'.HtmlFilter::encode($usersInfo['name']).'</a>
			</div>
		';
	}

	/**
	 * @param string $inputMessage
	 * @param Error[] $errors
	 * @param Exception|null $exception
	 */
	private function setError(string $inputMessage, array $errors = [], Exception $exception = null): void
	{
		if ($exception && $this->debugMode)
		{
			$message = $exception->getMessage();
		}
		else
		{
			$message = ($this->debugMode ? $this->getFirstErrorMessage($errors) : $inputMessage);
		}

		if ($message == '')
		{
			$message = $inputMessage;
		}
		$this->errorCollection->setError(new Error($message, $this->getFirstErrorCode($errors)));
	}

	/**
	 * @param Error[] $errors
	 */
	private function getFirstErrorMessage(array $errors): string
	{
		foreach ($errors as $error)
		{
			return (string) $error->getMessage();
		}
		return '';
	}

	/**
	 * @param Error[] $errors
	 */
	private function getFirstErrorCode(array $errors): string
	{
		foreach ($errors as $error)
		{
			return (string) $error->getCode();
		}
		return self::ERROR_UNKNOWN_SYSTEM_ERROR;
	}

	private function getEpicTaskIdsFromFilter(array $filter): array
	{
		$epicTaskIds = [];

		if (isset($filter['EPIC']) && (int)$filter['EPIC'])
		{
			$itemService = new ItemService();
			$epicTaskIds = $itemService->getTaskIdsByParentId($filter['EPIC']);
		}

		return $epicTaskIds;
	}

	private function removeItem(array $itemData, PushService $pushService = null)
	{
		$userId = (int)Util\User::getId();

		$item = ItemTable::createItemObject();
		$item->setId($itemData['itemId']);
		$item->setEntityId($itemData['entityId']);
		$item->setItemType($itemData['itemType']);

		$itemService = new ItemService();

		$response = '';

		switch ($item->getItemType())
		{
			case ItemTable::TASK_TYPE:
				$taskService = new TaskService($userId, $this->application);
				$item->setSourceId($itemData['sourceId']);
				$itemService->removeItem($item, $pushService, $taskService);
				if ($taskService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), $taskService->getErrors());
					return null;
				}
				break;
			case ItemTable::EPIC_TYPE:
				$response = $itemService->getEpicInfo($item->getId());
				$itemService->removeItem($item, $pushService);
				break;
			default:
				$itemService->removeItem($item, $pushService);
		}

		if ($itemService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), $itemService->getErrors());
			return null;
		}

		return $response;
	}

	private function getDefaultResponsibleId(int $groupId): int
	{
		if ($group = Workgroup::getById($groupId))
		{
			$scrumTaskResponsible = $group->getScrumTaskResponsible();
			return ($scrumTaskResponsible == 'A' ? $this->userId : $group->getScrumMaster());
		}

		return $this->userId;
	}

	private function getCounters(int $userId, int $groupId, $filterInstance): array
	{
		try
		{
			$counterInstance = Counter::getInstance($userId);
			$filterRole = $this->getFilterRole($filterInstance);
			return $counterInstance->getCounters($filterRole, $groupId);
		}
		catch (Exception $exception) {}

		return [];
	}

	private function getItemsData(
		array $itemIds,
		ItemService $itemService,
		TaskService $taskService = null,
		UserService $userService = null
	): array
	{
		if (empty($itemIds))
		{
			return [];
		}

		$items = $itemService->getItemsByIds($itemIds);

		$itemsData = $itemService->getItemsData($items);

		if ($taskService)
		{
			$entityService = new EntityService();
			$kanbanService = new KanbanService();

			$taskIds = [];
			$taskIdsForDynamicData = [];
			foreach ($items as $item)
			{
				$taskId = $item->getSourceId();

				$cacheService = new CacheService($taskId, CacheService::ITEM_TASKS);

				if ($cacheService->init())
				{
					$itemsData[$taskId] = array_merge($itemsData[$taskId], $cacheService->getData());

					$taskIdsForDynamicData[] = $taskId;
				}
				else
				{
					$taskIds[] = $taskId;
				}
			}

			$taskIdsForDynamicData = array_merge($taskIds, $taskIdsForDynamicData);

			$itemsData = $taskService->getItemsDynamicData($taskIdsForDynamicData, $itemsData);

			foreach ($taskIdsForDynamicData as $taskId)
			{
				$itemsData[$taskId] = $this->updateItemData(
					$itemsData[$taskId],
					$itemService,
					$entityService,
					$kanbanService,
					$userService
				);
			}

			TaskRegistry::getInstance()->load($taskIds, true);

			$tasksData = $taskService->getItemsData($taskIds);
			foreach ($tasksData as $taskId => $taskData)
			{
				$cacheService = new CacheService($taskId, CacheService::ITEM_TASKS);

				$cacheService->start();
				$cacheService->end($taskData);

				$itemData = array_merge($itemsData[$taskId], $taskData);

				$itemsData[$taskId] = $this->updateItemData(
					$itemData,
					$itemService,
					$entityService,
					$kanbanService,
					$userService
				);
			}
		}

		return array_values($itemsData);
	}

	private function updateItemData(
		array $itemData,
		ItemService $itemService,
		EntityService $entityService,
		KanbanService $kanbanService,
		UserService $userService = null
	): array
	{
		$itemData = $this->updateRelatedDataDependingOnTypeSprint(
			$itemData,
			$itemService,
			$entityService,
			$kanbanService
		);

		if ($userService && isset($itemData['responsibleId']))
		{
			$itemData['responsible'] = $userService->getInfoAboutUsers([$itemData['responsibleId']]);
		}

		return $itemData;
	}

	private function updateRelatedDataDependingOnTypeSprint(
		array $itemData,
		ItemService $itemService,
		EntityService $entityService,
		KanbanService $kanbanService
	): array
	{
		$entity = $entityService->getEntityById($itemData['entityId']);

		if ($entity->isActiveSprint())
		{
			if ($itemData['isParentTask'] === 'N' && !empty($itemData['completedSubTasksInfo']))
			{
				$itemData['isParentTask'] = 'Y';
			}

			if ($itemData['isParentTask'] === 'Y')
			{
				foreach ($itemData['completedSubTasksInfo'] as $sourceId => $subTaskInfo)
				{
					if ($kanbanService->isTaskInKanban($entity->getId(), $sourceId))
					{
						$itemData['subTasksInfo'][$sourceId] = $subTaskInfo;
					}
				}

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

	private function getFilterRole($filterInstance): string
	{
		$filterOptions = $filterInstance->getOptions();
		$filter = $filterOptions->getFilter();

		$possibleRoles = Counter\Role::getRoles();
		$role = Counter\Role::ALL;

		if (
			array_key_exists('ROLEID', $filter)
			&& array_key_exists($filter['ROLEID'], $possibleRoles)
		)
		{
			$role = $filter['ROLEID'];
		}

		return $role;
	}

	private function subscribeUserToPull(int $userId, int $groupId): void
	{
		$pullService = (Loader::includeModule('pull') ? new PullService($groupId) : null);
		if (!$pullService)
		{
			return;
		}

		$pullService->addSubscriber($userId);

		if ($this->getActiveTab($groupId) === 'plan')
		{
			$pullService->subscribeToEntityActions();
			$pullService->subscribeToItemActions();
		}
	}

	private function syncItemsWithTasks(int $backlogId, int $userId, int $groupId, array $filteredTaskIds): void
	{
		$entityService = new EntityService();

		$currentTaskIds = $entityService->getTaskIdsByGroupId($groupId);
		if ($entityService->getErrors())
		{
			return;
		}

		$itemService = new ItemService();

		$taskIdsToCreateItems = array_diff($filteredTaskIds, $currentTaskIds);

		foreach ($taskIdsToCreateItems as $taskId)
		{
			$item = $itemService->getItemBySourceId($taskId);
			if (!$itemService->getErrors() && $item->isEmpty())
			{
				$scrumItem = ItemTable::createItemObject();
				$scrumItem->setCreatedBy($userId);
				$scrumItem->setEntityId($backlogId);
				$scrumItem->setItemType(ItemTable::TASK_TYPE);
				$scrumItem->setSourceId($taskId);

				$itemService->createTaskItem($scrumItem);
			}
		}
	}

	private function getBorderColorByLinkedTasks(
		ItemService $itemService,
		array $linkedTaskIds,
		string $defaultColor
	): string
	{
		$borderColor = '';

		$itemsInfo = $itemService->getItemsInfoBySourceIds($linkedTaskIds);
		foreach ($itemsInfo as $info)
		{
			$borderColor = $info->getBorderColor();
		}

		if (!$borderColor)
		{
			$borderColor = $defaultColor;
		}

		return $borderColor;
	}

	private function getColorMapForItemsRelatedToEachOther(
		array $itemsToUpdateBorderColor,
		array $itemSourceIdsMap,
		array $itemLinkedTasksMap
	): array
	{
		$colorMap = [];

		foreach ($itemSourceIdsMap as $itemId => $taskId)
		{
			foreach ($itemLinkedTasksMap as $innerItemId => $linkedTaskIds)
			{
				if (in_array($taskId, $linkedTaskIds))
				{
					$colorMap[$itemId] = $itemsToUpdateBorderColor[$innerItemId];
					$colorMap[$innerItemId] = $itemsToUpdateBorderColor[$innerItemId];
				}
			}
		}

		return $colorMap;
	}

	private function getCancelButtonLayout(): string
	{
		return '<a class="ui-btn ui-btn-link" name="cancel">'
			.Loc::getMessage('TASKS_SCRUM_SPRINT_CLOSE_BUTTON').'</a>';
	}

	private function getNavToItems(int $pageNumber = 1): PageNavigation
	{
		$nav = new PageNavigation('entity-items');

		$nav->setPageSize(10);
		$nav->setCurrentPage($pageNumber);

		return $nav;
	}

	private function getNavToCompletedSprints(int $pageNumber = 1): PageNavigation
	{
		$nav = new PageNavigation('completed-sprints');

		$nav->setPageSize(10);
		$nav->setCurrentPage($pageNumber);

		return $nav;
	}
}
