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
use Bitrix\Main\Grid\Options as GridOptions;
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
use Bitrix\Tasks\Integration\SocialNetwork\Group as TaskGroupIntegration;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Util\Site as TaskSiteUtil;
use Bitrix\Tasks\Util\User as TasksUserUtil;

class TasksScrumComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TSC_01';

	private $application;
	private $errorCollection;
	private $userId;

	private $debugMode = false;

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
		$params['PROJECT_VIEW'] = (isset($params['PROJECT_VIEW']) && $params['PROJECT_VIEW'] == 'Y');

		$params['USER_ID'] = (!empty($params['USER_ID']) ? (int) $params['USER_ID'] : 0);

		return $params;
	}

	public function executeComponent()
	{
		try
		{
			$this->checkModules();

			$groupId = $this->arParams['GROUP_ID'];

			$this->setTitle();
			$this->init();

			if (!$this->canReadGroupTasks($groupId))
			{
				$this->includeErrorTemplate(Loc::getMessage('TASKS_SCRUM_ACCESS_TO_GROUP_DENIED'));
				return;
			}

			$request = Context::getCurrent()->getRequest();
			$this->debugMode = ($request->get('debug') == 'y');
			$this->arResult['debugMode'] = ($this->debugMode ? 'Y' : 'N');

			$this->saveActiveTab();
			$this->arResult['tabs'] = $this->getTabsInfo();

			$taskService = new TaskService($this->userId, $this->application);

			$this->arResult['tags'] = [];
			$this->arResult['tags']['task'] = $taskService->getTagsByUserIds([$this->userId]);

			$this->arResult['activeSprintId'] = 0;
			$this->arResult['activeSprint'] = [];

			$sprintService = new SprintService();
			$itemService = new ItemService();
			$sprint = $sprintService->getActiveSprintByGroupId($groupId, $itemService);

			$this->arResult['activeSprintId'] = ($sprintService->getErrors() ? 0 : $sprint->getId());
			if ($sprint->isActiveSprint())
			{
				$kanbanService = new KanbanService();

				$this->arResult['orderNewTask'] = $kanbanService->getKanbanSortValue($groupId);

				$this->arResult['activeSprintData'] = [
					'id' => $sprint->getId(),
					'name' => $sprint->getName(),
					'dateStart' => $sprint->getDateStart()->getTimestamp(),
					'dateEnd' => $sprint->getDateEnd()->getTimestamp(),
					'storyPoints' => $sprint->getStoryPoints(),
					'completedStoryPoints' => $sprintService->getCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					),
					'unCompletedStoryPoints' => $sprintService->getUnCompletedStoryPoints(
						$sprint,
						$kanbanService,
						$itemService
					),
					'completedTasks' => count($kanbanService->getFinishedTaskIdsInSprint($sprint->getId())),
					'unCompletedTasks' => count($kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId())),
					'status' => $sprint->getStatus(),
					'finishStatus' => $kanbanService->getFinishStatus()
				];
			}

			if ($this->getErrors())
			{
				$this->includeErrorTemplate(current($this->getErrors()), $this->getFirstErrorCode($this->getErrors()));
				return;
			}

			$this->filteredTaskIds = $taskService->getTaskIdsByFilter($groupId);

			$this->arResult['sprints'] = $this->getSprints($groupId);

			if ($this->getActiveTab() == 'active_sprint')
			{
				$this->includeComponentTemplate('active_sprint');
				return;
			}

			$group = Workgroup::getById($groupId);

			$this->arResult['defaultSprintDuration'] = $group->getDefaultSprintDuration();

			$backlog = $this->getBacklog($groupId);
			if ($backlog->isEmpty())
			{
				$backlog = $this->createNewBacklogForThisProject($groupId);
			}

			$this->arResult['tags']['epic'] = $itemService->getAllEpicTags($backlog->getId());

			$this->arResult['backlog'] = [
				'id' => $backlog->getId(),
				'storyPoints' => $backlog->getStoryPoints(),
				'items' => $this->prepareEntityItems($backlog)
			];

			if ($this->getErrors())
			{
				$this->includeErrorTemplate(current($this->getErrors()), $this->getFirstErrorCode($this->getErrors()));
				return;
			}

			$this->includeComponentTemplate('plan');
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

			$userId = (int) TasksUserUtil::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$taskService = new TaskService($userId, $this->application);
			$taskIds = $taskService->getTaskIdsByFilter($groupId);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			$items = [];
			if ($taskIds)
			{
				$itemService = new ItemService();
				$userService = new UserService();

				$backlogService = new BacklogService();
				$backlog = $backlogService->getBacklogByGroupId($groupId);
				$backlogItemIds = $itemService->getItemIdsBySourceIds($backlog->getId(), $taskIds);
				foreach ($backlogItemIds as $itemId)
				{
					$item = $itemService->getItemById($itemId);
					if (!$item->isEmpty())
					{
						$items[] = $this->getTaskItemFieldsForJs(
							$backlog->getEntityType(),
							$item,
							$taskService,
							$userService,
							$itemService
						);
					}
				}

				$sprintService = new SprintService();
				$listSprints = $sprintService->getSprintsByGroupId($groupId);
				foreach ($listSprints as $sprint)
				{
					$sprintItemIds = $itemService->getItemIdsBySourceIds($sprint->getId(), $taskIds);
					foreach ($sprintItemIds as $itemId)
					{
						$item = $itemService->getItemById($itemId);
						if (!$item->isEmpty())
						{
							$items[] = $this->getTaskItemFieldsForJs(
								$sprint->getEntityType(),
								$item,
								$taskService,
								$userService,
								$itemService
							);
						}
					}
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

			$userId = (int) TasksUserUtil::getId();

			$groupId = $this->arParams['GROUP_ID'];

			$taskItem = ItemTable::createItemObject();
			$taskItem->setEntityId($post['entityId']);
			$epicId = (int) $post['parentId'];
			if ($epicId)
			{
				$taskItem->setParentId($epicId);
			}
			$taskItem->setSort($post['sort']);
			$taskItem->setCreatedBy($userId);
			$taskItem->setStoryPoints($post['storyPoints']);
			$taskItem->setItemType(ItemTable::TASK_TYPE);

			$taskService = new TaskService($userId, $this->application);

			$group = Workgroup::getById($groupId);

			$itemService = new ItemService();
			$name = is_string($post['name'] ) ? $post['name'] : '';
			$name = $itemService->cleanEpicInTaskName($name);

			$taskFields = [
				'TITLE' => $name,
				'CREATED_BY' => $userId,
				'RESPONSIBLE_ID' => $group->getScrumMaster(),
				'GROUP_ID' => $groupId,
				'TAGS' => (is_array($post['tags']) ? $post['tags'] : []),
			];

			if (!empty($post['parentSourceId']))
			{
				$taskFields['PARENT_ID'] = (int) $post['parentSourceId'];
			}

			$taskId = $taskService->createTask($taskFields, $taskItem);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $taskService->getErrors());
				return null;
			}

			if (isset($post['isActiveSprint']) && $post['isActiveSprint'] == 'Y')
			{
				$kanbanService = new KanbanService();
				$kanbanService->addTasksToKanban($post['entityId'], [$taskId]);
				if ($kanbanService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $kanbanService->getErrors());
					return null;
				}
			}

			if (is_array($post['sortInfo']))
			{
				$itemService->moveAndSort($post['sortInfo']);
				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());
					return null;
				}
			}

			$userService = new UserService();
			$usersInfo = $userService->getInfoAboutUsers([$group->getScrumMaster()]);

			return [
				'sourceId' => $taskId,
				'entityId' => $taskItem->getEntityId(),
				'itemId' => $taskItem->getId(),
				'itemType' => $taskItem->getItemType(),
				'name' => $taskFields['TITLE'],
				'storyPoints' => $taskItem->getStoryPoints(),
				'parentId' => $taskItem->getParentId(),
				'sort' => $taskItem->getSort(),
				'responsible' => $usersInfo,
				'epic' => $itemService->getEpicInfo($taskItem->getParentId()),
				'tags' => $taskService->getTagsByTaskIds([$taskId])
			];
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), [], $exception);
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

			$userId = (int) TasksUserUtil::getId();

			$taskId = (int) $post['taskId'];
			$attachedIds = (is_array($post['attachedIds']) ? $post['attachedIds'] : []);

			$taskService = new TaskService($userId, $this->application);
			$ufValue = $taskService->attachFilesToTask($this->userFieldManager, $taskId, $attachedIds);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ATTACH_FILES_ERROR'), $taskService->getErrors());
				return null;
			}

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

			$userId = (int) TasksUserUtil::getId();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$tag = is_string($post['tag']) ? $post['tag'] : '';

			$taskService = new TaskService($userId, $this->application);
			$taskService->updateTagsList($taskId, [$tag]);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'), $taskService->getErrors());
				return null;
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

			$userId = (int) TasksUserUtil::getId();

			$taskId = (is_numeric($post['taskId']) ? (int) $post['taskId'] : 0);
			$tag = is_string($post['tag']) ? $post['tag'] : '';

			$taskService = new TaskService($userId, $this->application);
			if ($taskId && $tag)
			{
				$taskService->removeTags($taskId, $tag);
			}
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
			return null;
		}
	}

	public function attachEpicToItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$epicId = (int) $post['epicId'];
			$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);

			$itemService = new ItemService();

			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setParentId($epicId);

			$itemService->changeItem($item);
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

	public function deAttachEpicToItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);

			$itemService = new ItemService();

			$item = ItemTable::createItemObject();
			$item->setId($itemId);
			$item->setParentId(0);

			$itemService->changeItem($item);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}

			return '';
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), [], $exception);
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

			$userId = (int) TasksUserUtil::getId();
			$groupId = $this->arParams['GROUP_ID'];

			$sprintService = new SprintService();

			$sprint = $this->createSprint($sprintService, [
				'groupId' => $groupId,
				'name' => $post['name'],
				'sort' => $post['sort'],
				'userId' => $userId,
				'dateStart' => $post['dateStart'],
				'dateEnd' => $post['dateEnd'],
			]);

			if (is_array($post['sortInfo']))
			{
				$sprintService->changeSort($post['sortInfo']);
			}

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

			$userId = (int) TasksUserUtil::getId();

			$sprintService = new SprintService();

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($post['sprintId']);
			$sprint->setName($post['name']);

			$sprint = $sprintService->changeSprint($sprint);

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

			$userId = (int) TasksUserUtil::getId();

			$sprintService = new SprintService();

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($post['sprintId']);
			if (!empty($post['dateStart']))
			{
				$sprint->setDateStart(DateTime::createFromTimestamp($post['dateStart']));
			}
			if (!empty($post['dateEnd']))
			{
				$sprint->setDateEnd(DateTime::createFromTimestamp($post['dateEnd']));
			}

			$sprint = $sprintService->changeSprint($sprint);

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

	public function removeSprintAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$sprintService = new SprintService();

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($post['sprintId']);

			$sprintService->removeSprint($sprint);

			if (is_array($post['sortInfo']))
			{
				$sprintService->changeSort($post['sortInfo']);
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

			$itemService = new ItemService();
			$kanbanService = new KanbanService();

			$this->updateKanban($itemService, $kanbanService, $post);

			if (is_array($post['sortInfo']))
			{
				$itemService->moveAndSort($post['sortInfo']);
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

	public function updateItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);

			$itemService = new ItemService();

			switch ($post['itemType'])
			{
				case ItemTable::TASK_TYPE:
					if (isset($post['name']) && strlen($post['name']) > 0)
					{
						$item = $itemService->getItemById($itemId);
						if ($item->isEmpty())
						{
							$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'));
							return null;
						}
						$userId = (int) TasksUserUtil::getId();
						$taskService = new TaskService($userId, $this->application);
						$taskService->changeTask($item->getSourceId(), [
							'TITLE' => $post['name']
						]);
						if ($taskService->getErrors())
						{
							$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $taskService->getErrors());
							return null;
						}
					}
					break;
			}

			if (isset($post['storyPoints']))
			{
				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setStoryPoints($post['storyPoints']);
				$itemService->changeItem($item);
			}

			$kanbanService = new KanbanService();
			$this->updateKanban($itemService, $kanbanService, $post);
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $kanbanService->getErrors());
				return null;
			}

			if (is_array($post['sortInfo']))
			{
				$itemService->moveAndSort($post['sortInfo']);
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

	public function removeItemAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$userId = (int) TasksUserUtil::getId();

			$item = ItemTable::createItemObject();
			$item->setId($post['itemId']);
			$item->setItemType($post['itemType']);

			$itemService = new ItemService();

			$response = '';

			switch ($item->getItemType())
			{
				case ItemTable::TASK_TYPE:
					$taskService = new TaskService($userId, $this->application);
					$item->setSourceId($post['sourceId']);
					$itemService->removeItem($item, $taskService);
					if ($taskService->getErrors())
					{
						$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), $taskService->getErrors());
						return null;
					}
					break;
				case ItemTable::EPIC_TYPE:
					$response = $itemService->getEpicInfo($item->getId());
					$itemService->removeItem($item);
					break;
				default:
					$itemService->removeItem($item);
			}

			if (is_array($post['sortInfo']))
			{
				$itemService->moveAndSort($post['sortInfo']);
			}

			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), $itemService->getErrors());
				return null;
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

			$sprintService = new SprintService();
			if (is_array($post['sortInfo']))
			{
				$sprintService->changeSort($post['sortInfo']);
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

			$groupId = $this->arParams['GROUP_ID'];

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($post['sprintId']);
			$sprint->setGroupId($groupId);

			$sprintService = new SprintService();
			$itemService = new ItemService();
			$kanbanService = new KanbanService();

			if ($sprintService->isActiveSprint($sprint))
			{
				$this->errorCollection->setError(new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_ALREADY_ERROR')));
				return null;
			}

			$taskItems = $itemService->getTaskItemsByEntityId($sprint->getId());
			if (empty($taskItems))
			{
				$this->errorCollection->setError(new Error(Loc::getMessage('TASKS_SCRUM_SPRINT_START_NOT_TASKS_ERROR')));
				return null;
			}

			$sprint->setTaskIds($taskItems);
			$sprintService->startSprint($sprint, $kanbanService);

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

			$groupId = $this->arParams['GROUP_ID'];
			$userId = (int) TasksUserUtil::getId();

			$sprintService = new SprintService();
			$kanbanService = new KanbanService();
			$itemService = new ItemService();
			$backlogService = new BacklogService();

			$sprint = EntityTable::createEntityObject();
			$sprint->setId($post['sprintId']);
			$sprint->setGroupId($groupId);

			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$unFinishedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
			$totalTaskIds = array_merge($finishedTaskIds, $unFinishedTaskIds);
			$completedStoryPoints = $sprintService->getCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);
			$unCompletedStoryPoints = $sprintService->getUnCompletedStoryPoints(
				$sprint,
				$kanbanService,
				$itemService
			);
			$completedTasks = count($finishedTaskIds);
			$unCompletedTasks = count($unFinishedTaskIds);
			$sprint->setInfo([
				'event' => EntityTable::SPRINT_COMPLETED_EVENT,
				'storyPoints' => $itemService->getSumStoryPointsBySourceIds($totalTaskIds),
				'completedStoryPoints' => $completedStoryPoints,
				'unCompletedStoryPoints' => $unCompletedStoryPoints,
				'completedTasks' => $completedTasks,
				'unCompletedTasks' => $unCompletedTasks,
			]);

			$sprint = $sprintService->completeSprint($sprint);

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $sprintService->getErrors());
				return null;
			}
			if ($kanbanService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $kanbanService->getErrors());
				return null;
			}

			if ($post['direction'] == 'backlog')
			{
				$entity = $backlogService->getBacklogByGroupId($sprint->getGroupId());
			}
			else
			{
				$targetSprintId = (is_numeric($post['targetSprint']) ? (int) $post['targetSprint'] : 0);
				if ($targetSprintId)
				{
					$entity = $sprintService->getSprintById($targetSprintId);
				}
				else
				{
					$group = Workgroup::getById($groupId);
					$countSprints = count($sprintService->getSprintsByGroupId($groupId, $itemService));
					$entity = $this->createSprint($sprintService, [
						'groupId' => $sprint->getGroupId(),
						'name' => Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => $countSprints + 1]),
						'sort' => 0,
						'userId' => $userId,
						'dateStart' => time(),
						'dateEnd' => time() + $group->getDefaultSprintDuration(),
					]);
				}
			}

			$itemIds = $itemService->getItemIdsBySourceIds($sprint->getId(), $unFinishedTaskIds);

			if (!$sprintService->getErrors() && !$backlogService->getErrors())
			{
				$itemService->moveItemsToEntity($itemIds, $entity->getId());
			}

			$taskService = new TaskService($userId, $this->application);
			$taskService->completeTasks($finishedTaskIds);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $taskService->getErrors());
				return null;
			}

			if ($sprintService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_COMPLETE_ERROR'), $sprintService->getErrors());
				return null;
			}

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

			$userId = (int) TasksUserUtil::getId();
			$taskService = new TaskService($userId, $this->application);
			$taskService->changeTask($post['sourceId'], [
				'RESPONSIBLE_ID' => (int) $post['responsible']['id']
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

			$userId = (int) TasksUserUtil::getId();

			$epic = ItemTable::createItemObject();
			$epic->setItemType(ItemTable::EPIC_TYPE);
			$epic->setEntityId($post['entityId']);
			$epic->setName($post['name']);
			$epic->setDescription($post['description']);
			$epic->setSort(0);
			$epic->setCreatedBy($userId);

			$info = [
				'color' => $post['color']
			];
			$epic->setInfo($info);

			$itemService = new ItemService();
			$epic = $itemService->createItem($epic);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_ADD_ERROR'), $itemService->getErrors());
				return null;
			}

			$files = (is_array($post['files']) ? $post['files'] : []);
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

			$epicId = (int) $post['id'];

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

			$userId = (int) TasksUserUtil::getId();

			$epic = ItemTable::createItemObject();
			$epic->setId($post['epicId']);
			$epic->setItemType(ItemTable::EPIC_TYPE);
			$epic->setEntityId($post['entityId']);
			$epic->setName($post['name']);
			$epic->setDescription($post['description']);
			$epic->setSort(0);
			$epic->setModifiedBy($userId);

			$info = [
				'color' => $post['color']
			];
			$epic->setInfo($info);

			$itemService = new ItemService();
			$itemService->changeItem($epic);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_EPIC_EDIT_ERROR'), $itemService->getErrors());
				return null;
			}

			$files = (is_array($post['files']) ? $post['files'] : []);
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

	public function getEpicsListAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();
			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

			$this->userId = (int) TasksUserUtil::getId();

			$isGridRequest = ($request->get('grid_id') != null);

			$entityId = (int) $post['entityId'];
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
						'USER' => $this->getEpicGridColumnUser($usersInfo),
					],
					'actions' => [
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_VIEW'),
							'onclick' => 'BX.Tasks.Scrum.ScrumManager.openEpicViewForm("'.$epic->getId().'");',
						],
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_EDIT'),
							'onclick' => 'BX.Tasks.Scrum.ScrumManager.openEpicEditForm("'.$epic->getId().'");',
						],
						[
							'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_REMOVE'),
							'onclick' => 'BX.Tasks.Scrum.ScrumManager.removeEpic("'.$epic->getId().'");',
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

			$buttons = ['UploadImage', 'UploadFile', 'CreateLink'];

			$description = ($post['text'] ? $post['text'] : '');
			$description = str_replace("\r\n", "\n", $description);

			$epicId = (isset($post['epicId']) ? (int) $post['epicId'] : 0);

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
				'NAME_TEMPLATE' => TaskSiteUtil::getUserNameFormat(),
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

			$userId = (int) TasksUserUtil::getId();

			$epicId = (int) $post['epicId'];
			$description = ($post['text'] ? $post['text'] : '');
			$description = str_replace("\r\n", "\n", $description);

			$itemService = new ItemService();
			$userFields = $itemService->getUserFields($this->userFieldManager, $epicId);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $itemService->getErrors());
				return null;
			}

			$taskService = new TaskService($userId, $this->application);
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

			$epicId = (int) $post['epicId'];

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
				'BUTTONS' => ['save', 'cancel']
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
					'cancel'
				]
			]);
		}
		catch (\Exception $exception)
		{
			return '';
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
		return TaskGroupIntegration::canReadGroupTasks($this->userId, $groupId);
	}

	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ? $code : self::ERROR_UNKNOWN_SYSTEM_ERROR);
		$this->includeComponentTemplate('error');
	}

	private function init()
	{
		if (empty($this->arParams['USER_ID']))
		{
			$this->userId = (int) TasksUserUtil::getId();
		}
		else
		{
			$this->userId = (int) $this->arParams['USER_ID'];
		}
	}

	private function setTitle()
	{
		if ($this->arParams['SET_TITLE'])
		{
			$this->application->setTitle(Loc::getMessage('TASKS_SCRUM_TITLE'));
		}
	}

	/**
	 * @param int $groupId
	 * @return EntityTable
	 * @throws SystemException
	 */
	private function getBacklog(int $groupId): EntityTable
	{
		$backlogService = new BacklogService();
		$itemService = new ItemService();

		$backlog = $backlogService->getBacklogByGroupId($groupId, $itemService);

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

	/**
	 * @param int $groupId
	 * @return array EntityTable[]
	 * @throws SystemException
	 */
	private function getSprints(int $groupId): array
	{
		$sprintService = new SprintService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$listSprints = $sprintService->getSprintsByGroupId($groupId, $itemService);

		if ($sprintService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
		}

		$sprints = [];

		foreach ($listSprints as $sprint)
		{
			$finishedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());

			$storyPoints = '';
			$completedStoryPoints = '';
			$unCompletedStoryPoints = '';
			$completedTasks = 0;
			$unCompletedTasks = 0;

			if ($sprint->isCompletedSprint())
			{
				foreach ($sprint->getInfo() as $info)
				{
					if ($info['event'] == EntityTable::SPRINT_COMPLETED_EVENT)
					{
						$storyPoints = $info['storyPoints'];
						$completedStoryPoints = $info['completedStoryPoints'];
						$unCompletedStoryPoints = $info['unCompletedStoryPoints'];
						$completedTasks = $info['completedTasks'];
						$unCompletedTasks = $info['unCompletedTasks'];
					}
				}
			}
			else
			{
				$storyPoints = $sprint->getStoryPoints();
				$completedStoryPoints = $sprintService->getCompletedStoryPoints(
					$sprint,
					$kanbanService,
					$itemService
				);
				$unCompletedStoryPoints = $sprintService->getUnCompletedStoryPoints(
					$sprint,
					$kanbanService,
					$itemService
				);
				$completedTasks = count($finishedTaskIds);
				$unCompletedTasks = count($kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId()));
			}

			$sprints[] = [
				'id' => $sprint->getId(),
				'name' => $sprint->getName(),
				'sort' => $sprint->getSort(),
				'dateStart' => $sprint->getDateStart()->getTimestamp(),
				'dateEnd' => $sprint->getDateEnd()->getTimestamp(),
				'storyPoints' => $storyPoints,
				'completedStoryPoints' => $completedStoryPoints,
				'unCompletedStoryPoints' => $unCompletedStoryPoints,
				'completedTasks' => $completedTasks,
				'unCompletedTasks' => $unCompletedTasks,
				'status' => $sprint->getStatus(),
				'items' => $this->prepareEntityItems($sprint)
			];
		}

		return $sprints;
	}

	private function prepareEntityItems(EntityTable $entity): array
	{
		$taskService = new TaskService($this->userId, $this->application);
		$userService = new UserService();
		$itemService = new ItemService();

		return $this->makeListItems(
			$entity->getEntityType(),
			$entity->getChildren(),
			$itemService,
			$taskService,
			$userService
		);
	}

	private function makeListItems(
		string $entityType,
		array $items,
		ItemService $itemService,
		TaskService $taskService,
		UserService $userService
	): array
	{
		$listItems = [];

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
						$listItems[] = $this->getTaskItemFieldsForJs(
							$entityType,
							$item,
							$taskService,
							$userService,
							$itemService
						);
					}
					break;
			}
			$children = $item->getChildren();
			if ($children)
			{
				$listItems = array_merge(
					$listItems,
					$this->makeListItems($entityType, $children, $itemService, $taskService, $userService)
				);
			}
		}

		return $listItems;
	}

	private function getTaskItemFieldsForJs(
		string $entityType,
		ItemTable $item,
		TaskService $taskService,
		UserService $userService,
		ItemService $itemService
	): array
	{
		$itemData = [
			'itemId' => $item->getId(),
			'entityId' => $item->getEntityId(),
			'itemType' => $item->getItemType(),
			'storyPoints' => $item->getStoryPoints(),
			'parentId' => $item->getParentId(),
			'sort' => $item->getSort(),
			'sourceId' => $item->getSourceId()
		];

		$itemData['entityType'] = $entityType;

		$epicInfo = $itemService->getEpicInfo($item->getParentId());
		$itemData['epic'] = $epicInfo;

		$taskId = $item->getSourceId();
		$taskInfo = $taskService->getTaskInfo($taskId);
		$checkListCounts = $taskService->getChecklistCounts($taskId);
		$itemData['name'] = $taskInfo['TITLE'];
		$itemData['tags'] = $taskInfo['TAGS'];
		$itemData['attachedFilesCount'] = $taskService->getAttachedFilesCount($this->userFieldManager, $taskId);
		$itemData['checkListComplete'] = $checkListCounts['complete'];
		$itemData['checkListAll'] = $checkListCounts['all'];
		$itemData['newCommentsCount'] = $taskService->getNewCommentsCount($taskId);
		$itemData['completed'] = ($taskService->isCompletedTask($taskId) ? 'Y' : 'N');

		$this->setTaskTags($taskInfo['TAGS']);

		$usersInfo = $userService->getInfoAboutUsers([$taskInfo['RESPONSIBLE_ID']]);
		$itemData['responsible'] = $usersInfo;

		return $itemData;
	}

	private function getTabsInfo(): array
	{
		$request = Context::getCurrent()->getRequest();
		$uri = new Uri($request->getRequestUri());

		$uri->addParams(['tab' => 'plan']);
		$planningUrl = $uri->getUri();

		$uri->addParams(['tab' => 'active_sprint']);
		$activeSprintUrl = $uri->getUri();

		return [
			'planning' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_PLAN'),
				'url' => $planningUrl,
				'active' => ($this->getActiveTab() == 'plan')
			],
			'activeSprint' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_SPRINT'),
				'url' => $activeSprintUrl,
				'active' => ($this->getActiveTab() == 'active_sprint')
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
	}

	private function getActiveTab()
	{
		return CUserOptions::getOption('tasks.scrum.'.$this->arParams['GROUP_ID'], 'active_tab', 'plan');
	}

	private function createSprint(SprintService $sprintService, array $fields): EntityTable
	{
		$sprint = EntityTable::createEntityObject();
		$sprint->setGroupId($fields['groupId']);
		$sprint->setName($fields['name']);
		$sprint->setSort($fields['sort']);
		$sprint->setCreatedBy($fields['userId']);
		$sprint->setModifiedBy($fields['userId']);
		$sprint->setDateStart(DateTime::createFromTimestamp($fields['dateStart']));
		$sprint->setDateEnd(DateTime::createFromTimestamp($fields['dateEnd']));

		return $sprintService->createSprint($sprint);
	}

	private function updateKanban(ItemService $itemService, KanbanService $kanbanService, array $post): void
	{
		$itemId = (is_numeric($post['itemId']) ? (int) $post['itemId'] : 0);

		if ($this->isMoveToAnotherEntity($post))
		{
			if ($this->isTaskMoveToActiveSprint($post))
			{
				$taskId = $itemService->getTaskIdByItemId($itemId);
				if ($taskId)
				{
					$kanbanService->addTasksToKanban($post['entityId'], [$taskId]);
				}
			}
			if ($this->isTaskMoveFromActiveSprint($post))
			{
				$taskId = $itemService->getTaskIdByItemId($itemId);
				if ($taskId)
				{
					$kanbanService->removeTasksFromKanban([$taskId]);
				}
			}
		}
	}

	private function isMoveToAnotherEntity(array $post): bool
	{
		return isset($post['entityId']);
	}

	private function isTaskMoveToActiveSprint(array $post): bool
	{
		return (
			$post['itemType'] == ItemTable::TASK_TYPE &&
			isset($post['toActiveSprint']) &&
			$post['toActiveSprint'] == 'Y'
		);
	}

	private function isTaskMoveFromActiveSprint(array $post): bool
	{
		return (
			$post['itemType'] == ItemTable::TASK_TYPE &&
			isset($post['fromActiveSprint']) &&
			$post['fromActiveSprint'] == 'Y'
		);
	}

	private function getGridOrder(string $gridId): array
	{
		$defaultSort = ['ID' => 'DESC'];

		$gridOptions = new GridOptions($gridId);
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
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_NAME'),
				'default' => true,
				'sort' => 'NAME',
			],
			[
				'id' => 'TAGS',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TAGS'),
				'default' => true
			],
			[
				'id' => 'USER',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_USER'),
				'default' => true
			]
		];
	}

	private function getEpicGridColumnName(ItemTable $epic): string
	{
		$info = $epic->getInfo();
		$color = HtmlFilter::encode($info['color']);
		$name = HtmlFilter::encode($epic->getName());
		return '
			<div class="tasks-scrum-epic-grid-name">
				<div class="tasks-scrum-epic-grid-name-color" style="background-color: '.$color.';"></div>
				<a onclick="BX.Tasks.Scrum.ScrumManager.openEpicViewForm(\''.$epic->getId().'\')" class=
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
			$tagsNodes[] = '<div class="tasks-scrum-epic-grid-tag">'.HtmlFilter::encode($tagName).'</div>';
		}

		return '
			<div class="tasks-scrum-epic-grid-tags">
				'.implode('', $tagsNodes).'
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

	private function setTaskTags(array $taskTags): void
	{
		foreach ($taskTags as $taskTag)
		{
			$this->arResult['tags']['task'][$taskTag] = $taskTag;
		}
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
}