<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

require_once __DIR__ . '/accesscheck.php';

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TagAccessController;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Component\Scrum;
use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Form\ItemInfo;
use Bitrix\Tasks\Scrum\Form\TypeForm;
use Bitrix\Tasks\Scrum\Service\BacklogService;
use Bitrix\Tasks\Scrum\Service\CacheService;
use Bitrix\Tasks\Scrum\Service\DefinitionOfDoneService;
use Bitrix\Tasks\Scrum\Service\EntityService;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\RobotService;
use Bitrix\Tasks\Scrum\Service\TypeService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PullService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Scrum\Utility\StoryPoints;
use Bitrix\Tasks\Scrum\Utility\ViewHelper;
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
		$groupId = (int) $this->arParams['GROUP_ID'];

		$basePrefilters = [
			new Scrum\AccessCheck($groupId),
		];

		return [
			'applyFilter' => [
				'prefilters' => $basePrefilters
			],
			'createTask' => [
				'prefilters' => $basePrefilters
			],
			'getCurrentState' => [
				'prefilters' => $basePrefilters
			],
			'hasTaskInFilter' => [
				'prefilters' => $basePrefilters
			],
			'attachFilesToTask' => [
				'prefilters' => $basePrefilters
			],
			'updateTaskTags' => [
				'prefilters' => $basePrefilters
			],
			'removeTaskTags' => [
				'prefilters' => $basePrefilters
			],
			'updateItemEpic' => [
				'prefilters' => $basePrefilters
			],
			'updateItemEpics' => [
				'prefilters' => $basePrefilters
			],
			'createSprint' => [
				'prefilters' => $basePrefilters
			],
			'changeSprintName' => [
				'prefilters' => $basePrefilters
			],
			'changeSprintDeadline' => [
				'prefilters' => $basePrefilters
			],
			'getSprintCompletedItems' => [
				'prefilters' => $basePrefilters
			],
			'removeSprint' => [
				'prefilters' => $basePrefilters
			],
			'updateItemSort' => [
				'prefilters' => $basePrefilters
			],
			'updateItem' => [
				'prefilters' => $basePrefilters
			],
			'removeItems' => [
				'prefilters' => $basePrefilters
			],
			'updateSprintSort' => [
				'prefilters' => $basePrefilters
			],
			'changeTaskResponsible' => [
				'prefilters' => $basePrefilters
			],
			'getSubTaskItems' => [
				'prefilters' => $basePrefilters
			],
			'getItems' => [
				'prefilters' => $basePrefilters
			],
			'getCompletedSprints' => [
				'prefilters' => $basePrefilters
			],
			'getCompletedSprintsStatsAction' => [
				'prefilters' => $basePrefilters
			],
			'saveShortView' => [
				'prefilters' => $basePrefilters
			],
			'saveSprintVisibility' => [
				'prefilters' => $basePrefilters
			],
			'showLinkedTasks' => [
				'prefilters' => $basePrefilters
			],
			'getItemData' => [
				'prefilters' => $basePrefilters
			],
			'getSprintData' => [
				'prefilters' => $basePrefilters
			],
		];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'GROUP_ID',
			'OWNER_ID',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_USER_TASKS',
			'PATH_TO_GROUP_TASKS_TASK',
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

		$params['CONTEXT'] = (empty($params['CONTEXT'] ?? null) ? 'group' : $params['CONTEXT']);

		$params['SET_TITLE'] = (isset($params['SET_TITLE']) && $params['SET_TITLE'] == 'Y');

		$params['OWNER_ID'] = (!empty($params['USER_ID']) ? (int)$params['USER_ID'] : 0);

		$params['PATH_TO_USER_TASKS'] = ($params['PATH_TO_USER_TASKS'] ?? '');
		$params['PATH_TO_GROUP_TASKS'] = ($params['PATH_TO_GROUP_TASKS'] ?? '');
		$params['PATH_TO_SCRUM_TEAM_SPEED'] = ($params['PATH_TO_SCRUM_TEAM_SPEED'] ?? '');
		$params['PATH_TO_SCRUM_BURN_DOWN'] = ($params['PATH_TO_SCRUM_BURN_DOWN'] ?? '');

		return $params;
	}

	public function executeComponent()
	{
		$this->checkModules();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$this->setTitle();
		$this->init();

		if (!$this->isScrumEnabled())
		{
			$this->includeComponentTemplate('scrum_disabled');

			return;
		}

		if (!$this->canReadGroupTasks($groupId))
		{
			$this->includeErrorTemplate(Loc::getMessage('TASKS_SCRUM_ACCESS_TO_GROUP_DENIED'));

			return;
		}

		$request = Context::getCurrent()->getRequest();

		$this->debugMode = ($request->get('debug') === 'y');
		$this->frameMode = ($request->get('IFRAME') === 'Y');

		$viewHelper = new ViewHelper($this->getSiteId());
		$viewHelper->saveActiveView($request->get('tab'), $groupId);
		$activeTab = $viewHelper->getActiveView($groupId);

		$this->subscribeUserToPull($this->userId, $groupId);

		$this->arResult['debugMode'] = ($this->debugMode ? 'Y' : 'N');
		$this->arResult['frameMode'] = ($this->frameMode ? 'Y' : 'N');
		$this->arResult['views'] = $this->getViewsInfo($groupId);
		$this->arResult['culture'] = $this->getCultureInfo();

		switch ($activeTab)
		{
			case 'plan':
				$this->includePlanTemplate($groupId);
				break;
			case 'active_sprint':
				$this->includeActiveSprintTemplate($groupId);
				break;
			case 'completed_sprint':
				$sprintId = (int)$request->get('sprintId');
				$this->includeCompletedSprintTemplate($groupId, $sprintId);
				break;
		}
	}

	public function applyFilterAction()
	{
		try
		{
			$this->checkModules();

			$request = Context::getCurrent()->getRequest();
			$post = $request->getPostList()->toArray();

			$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
			$this->userId = Util\User::getId();

			$pageSize = (is_numeric($post['pageSize'] ?? null) ? (int) $post['pageSize'] : 1);

			$groupId = (int) $this->arParams['GROUP_ID'];

			$taskService = new TaskService($this->userId);

			$filterInstance = $taskService->getFilterInstance($groupId);

			$this->arResult['isExactSearchApplied'] = $isExactSearchApplied = $filterInstance->isExactSearchApplied();

			$sprintService = new SprintService($this->userId);

			$backlog = $this->getBacklog($groupId);
			$backlogItems = $this->getEntityItems(
				$groupId,
				[$backlog->getId()],
				$this->getNavToItems($backlog->getId(), 1, $pageSize)
			);

			$sprints = $sprintService->getUncompletedSprints($groupId);

			$completedSprintsData = [];
			if ($isExactSearchApplied)
			{
				$completedSprints = $sprintService->getCompletedSprints($groupId);

				$entityService = new EntityService();
				$itemService = new ItemService();
				$kanbanService = new KanbanService();

				$sprintViews = $this->getViewsInfo($groupId);

				foreach ($completedSprints as $sprint)
				{
					$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

					$completedSprintsData[] = $this->prepareSprintData(
						$sprint,
						$sprintViews,
						$cacheService,
						$entityService,
						$sprintService,
						$itemService,
						$kanbanService
					);
				}

				$sprints = array_merge($sprints, $completedSprints);
			}

			$sprintIds = [];
			foreach ($sprints as $sprint)
			{
				$sprintIds[] = $sprint->getId();
			}

			$sprintItems = $this->getEntityItems(
				$groupId,
				$sprintIds,
				$this->getNavToItems('sprints', 1, $pageSize * count($sprintIds))
			);

			return [
				'isExactSearchApplied' => $isExactSearchApplied ? 'Y' : 'N',
				'items' => array_merge($backlogItems, $sprintItems),
				'completedSprints' => $completedSprintsData,
			];
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

			$tmpId = (is_string($post['tmpId'] ?? null) ? $post['tmpId'] : '');
			$name = (is_string($post['name'] ?? null) ? $post['name'] : '');
			$entityId = (is_numeric($post['entityId'] ?? null) ? (int) $post['entityId'] : 0);
			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
			$parentTaskId = (is_numeric($post['parentTaskId'] ?? null) ? (int) $post['parentTaskId'] : 0);
			$storyPoints = (is_string($post['storyPoints'] ?? null) ? $post['storyPoints'] : '');
			$sort = (is_numeric($post['sort']) ? (int) $post['sort'] : 0);
			$responsible = (is_array($post['responsible'] ?? null) ? $post['responsible'] : []);
			$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);
			$info = (is_array($post['info'] ?? null) ? $post['info'] : []);

			if (empty($name))
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_NAME_ERROR'));

				return null;
			}

			$groupId = (int) $this->arParams['GROUP_ID'];

			$entityService = new EntityService();
			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);
			$taskService = new TaskService($this->userId, $this->application);

			$entity = $entityService->getEntityById($entityId);
			if ($entity->isEmpty())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));

				return null;
			}

			$item = new ItemForm();

			$item->setEntityId($entity->getId());
			if ($epicId)
			{
				$item->setEpicId($epicId);
			}
			$item->setSort($sort);
			$item->setCreatedBy($this->userId);
			$item->setStoryPoints($storyPoints);

			$responsibleId = (is_numeric($responsible['id'] ?? null) ? (int) $responsible['id'] : 0);
			if (!$responsibleId)
			{
				$responsibleId = $this->getDefaultResponsibleId($groupId);
			}

			$taskFields = [
				'TITLE' => $name,
				'CREATED_BY' => $this->userId,
				'RESPONSIBLE_ID' => $responsibleId,
				'GROUP_ID' => $groupId,
			];

			$isDecompositionAction = ($parentTaskId > 0);

			if ($isDecompositionAction && $entity->getEntityType() === 'sprint')
			{
				$taskFields['PARENT_ID'] = $parentTaskId;

				$parentItem = $itemService->getItemBySourceId($parentTaskId);

				$itemService->changeItem($parentItem);
			}

			$taskId = $taskService->createTask($taskFields);
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $taskService->getErrors());

				return null;
			}

			$createdItem = $itemService->getItemBySourceId($taskId);

			$item->setId($createdItem->getId());
			$item->setSourceId($createdItem->getSourceId());
			$item->setTmpId($tmpId);

			if ($isDecompositionAction && $entity->getEntityType() === 'backlog')
			{
				$taskService->updateTaskLinks($parentTaskId, $taskId);
				$taskService->updateTaskLinks($taskId, $parentTaskId);

				$itemInfo = new ItemInfo();
				if (!empty($info[$itemInfo->getBorderColorKey()]))
				{
					$borderColor = $info[$itemInfo->getBorderColorKey()];

					$createdItem = $this->setBorderColor($createdItem, $borderColor);
					$item->setInfo($createdItem->getInfo());

					$parentItem = $itemService->getItemBySourceId($parentTaskId);
					$parentItem = $this->setBorderColor($parentItem, $borderColor);
					$itemService->changeItem($parentItem);
				}
			}

			$itemService->changeItem($item, $pushService);
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());

				return null;
			}

			if ($entity->isActiveSprint())
			{
				$kanbanService = new KanbanService();
				$kanbanService->addTasksToKanban($entity->getId(), [$taskId]);
				if ($kanbanService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $kanbanService->getErrors());

					return null;
				}
			}

			if ($sortInfo)
			{
				$itemService->sortItems($this->prepareSortInfo($groupId, $sortInfo), $pushService);
				if ($itemService->getErrors())
				{
					$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());

					return null;
				}
			}

			$data = $this->getItemsData(
				$groupId,
				[$item->getId()],
				$itemService,
				$taskService,
				new UserService()
			)[0];
			if ($itemService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $itemService->getErrors());

				return null;
			}
			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), $taskService->getErrors());

				return null;
			}

			return $data;
		}
		catch (\Exception $exception)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ADD_ERROR'), [], $exception);

			return null;
		}
	}

	public function getCurrentStateAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$groupId = (int) $this->arParams['GROUP_ID'];
		$ownerId = $this->arParams['OWNER_ID'];

		$taskId = (is_numeric($post['taskId'] ?? null) ? (int) $post['taskId'] : 0);

		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);
		$taskService->setOwnerId($ownerId);

		$item = $itemService->getItemBySourceId($taskId);

		return [
			'itemData' => $this->getItemsData(
				$groupId,
				[$item->getId()],
				$itemService,
				$taskService,
				new UserService()
			)[0],
		];
	}

	public function hasTaskInFilterAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$taskId = (is_numeric($post['taskId'] ?? null) ? (int)$post['taskId'] : 0);

		$taskService = new TaskService($this->userId);

		$filterInstance = $taskService->getFilterInstance($groupId);

		$filter = $taskService->getFilter($filterInstance);

		$filter['ID'] = $taskId;
		$filter['CHECK_PERMISSIONS'] = 'N';

		return [
			'has' => !empty($taskService->getTaskIdsByFilter($filter)),
		];
	}

	public function attachFilesToTaskAction()
	{
		$this->checkModules();
		if (!ModuleManager::isModuleInstalled('disk'))
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_INCLUDE_MODULE'));
		}

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$attachedIds = (is_array($post['attachedIds'] ?? null) ? $post['attachedIds'] : []);
		$attachedFilesCount = [];

		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);

		$items = [];
		foreach ($itemIds as $itemId)
		{
			$itemId = (is_numeric($itemId) ? (int) $itemId : 0);

			$item = $itemService->getItemById($itemId);
			if (!$item->isEmpty())
			{
				$taskId = $item->getSourceId();

				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
				{
					$ufValue = $taskService->attachFilesToTask($this->userFieldManager, $taskId, $attachedIds);

					(new CacheService($taskId, CacheService::ITEM_TASKS))->clean();

					$items[] = $item;

					$attachedFilesCount[$itemId] = count($ufValue);
				}
			}
		}

		if ($taskService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_ATTACH_FILES_ERROR'), $taskService->getErrors());

			return null;
		}

		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		if ($pushService)
		{
			foreach ($items as $item)
			{
				$pushService->sendUpdateItemEvent($item);
			}
		}

		return [
			'attachedFilesCount' => $attachedFilesCount
		];
	}

	public function updateTaskTagsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$tag = (is_string($post['tag'] ?? null) ? $post['tag'] : '');
		$groupId = (int) $this->arParams['GROUP_ID'];
		$params = ['GROUP_ID' => $groupId];

		$tagService = new Tag($this->userId);
		$actionAdd = isset($post['action']) && $post['action'] === 'add';
		if ($actionAdd)
		{
			if (
				!TagAccessController::can($this->userId, ActionDictionary::ACTION_TAG_SEARCH, null, $params)
			)
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TASKS_SCRUM_TAG_ACCESS_DENIED'),
				];
			}

			if ($tagService->isExistsByGroup($groupId, $tag))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TASKS_SCRUM_TAG_ALREADY_EXISTS'),
				];
			}
		}
		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);

		$items = [];
		foreach ($itemIds as $itemId)
		{
			$itemId = (is_numeric($itemId) ? (int) $itemId : 0);

			$item = $itemService->getItemById($itemId);
			if (!$item->isEmpty())
			{
				if (
					TaskAccessController::can(
						$this->userId,
						ActionDictionary::ACTION_TASK_EDIT,
						$item->getSourceId()
					)
				)
				{
					$taskService->updateTagsList($item->getSourceId(), [$tag]);

					$items[] = $item;
				}
			}
		}

		if ($taskService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_TASK_TAG_ADD_ERROR'), $taskService->getErrors());

			return null;
		}

		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		if ($pushService)
		{
			foreach ($items as $item)
			{
				$pushService->sendUpdateItemEvent($item);
			}
		}

		return [
			'success' => true,
			'error' => '',
			'group' => WorkgroupTable::getByPrimary($groupId)->fetchObject()->getName(),
		];
	}

	public function removeTaskTagsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$tag = (is_string($post['tag'] ?? null) ? $post['tag'] : '');

		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);

		$items = [];
		foreach ($itemIds as $itemId)
		{
			$itemId = (is_numeric($itemId) ? (int) $itemId : 0);

			$item = $itemService->getItemById($itemId);
			if (!$item->isEmpty() && $tag)
			{
				if (
					TaskAccessController::can(
						$this->userId,
						ActionDictionary::ACTION_TASK_EDIT,
						$item->getSourceId()
					)
				)
				{
					$taskService->removeTags($item->getSourceId(), $tag);

					$items[] = $item;
				}
			}
		}

		if ($taskService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'), $taskService->getErrors());
			return null;
		}

		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		if ($pushService)
		{
			foreach ($items as $item)
			{
				$pushService->sendUpdateItemEvent($item);
			}
		}

		return '';
	}

	public function updateItemEpicsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$epicId = (is_numeric($post['epicId'] ?? null) ? (int) $post['epicId'] : 0);

		$itemService = new ItemService();
		$epicService = new EpicService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$epic = $epicService->getEpic($epicId);
		$epicId = $epic->getId() ?: 0;

		foreach ($itemIds as $itemId)
		{
			$itemId = (is_numeric($itemId) ? (int) $itemId : 0);

			$item = $itemService->getItemById($itemId);
			if (
				!$item->isEmpty()
				&& TaskAccessController::can(
					$this->userId,
					ActionDictionary::ACTION_TASK_READ,
					$item->getSourceId()
				)
			)
			{
				$item->setEpicId($epicId);

				$itemService->changeItem($item, $pushService);
			}
			else
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
			}
		}

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		return [
			'epic' => $epic->toArray(),
		];
	}

	public function createSprintAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$tmpId = (is_string($post['tmpId'] ?? null) ? $post['tmpId'] : '');
		$name = (is_string($post['name'] ?? null) ? $post['name'] : '');
		$sort = (is_numeric($post['sort'] ?? null) ? (int) $post['sort'] : 0);
		$inputDateStart = (is_numeric($post['dateStart'] ?? null) ? (int) $post['dateStart'] : 0);
		$inputDateEnd = (is_numeric($post['dateEnd'] ?? null) ? (int) $post['dateEnd'] : 0);

		$groupId = (int) $this->arParams['GROUP_ID'];

		$sprintService = new SprintService($this->userId);

		if ($name === '')
		{
			$countSprints = count($sprintService->getSprintsByGroupId($groupId));

			$name = Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => $countSprints + 1]);
		}

		$dateStart = $sprintService->getDateEndFromLastPlannedSprint($groupId);
		if ($dateStart)
		{
			$group = Workgroup::getById($groupId);
			$dateEnd = $dateStart + $group->getDefaultSprintDuration();
		}
		else
		{
			$dateStart = $inputDateStart;
			$dateEnd = $inputDateEnd;
		}

		$sprint = $this->createSprint($sprintService, [
			'groupId' => $groupId,
			'tmpId' => $tmpId,
			'name' => $name,
			'sort' => $sort,
			'userId' => $this->userId,
			'dateStart' => $dateStart,
			'dateEnd' => $dateEnd,
			'status' => EntityForm::SPRINT_PLANNED,
		]);

		if ($sprintService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_ADD_ERROR'), $sprintService->getErrors());
			return null;
		}

		return $sprintService->getSprintData($sprint);
	}

	public function changeSprintNameAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$sprintId = (is_numeric($post['sprintId'] ?? null) ? (int) $post['sprintId'] : 0);
		$name = (is_string($post['name'] ?? null) ? $post['name'] : 'The sprint');

		$sprintService = new SprintService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = $sprintService->getSprintById($sprintId);
		if ($sprint->isEmpty() || !$this->canReadGroupTasks($sprint->getGroupId()))
		{
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_NAME_ERROR'),
				$sprintService->getErrors()
			);

			return null;
		}

		$sprint->setName($name);
		$sprint->setModifiedBy(Util\User::getId());

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

	public function changeSprintDeadlineAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$sprintId = (is_numeric($post['sprintId'] ?? null) ? (int) $post['sprintId'] : 0);
		$dateStart = (is_numeric($post['dateStart'] ?? null) ? (int) $post['dateStart'] : 0);
		$dateEnd = (is_numeric($post['dateEnd'] ?? null) ? (int) $post['dateEnd'] : 0);

		$sprintService = new SprintService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = $sprintService->getSprintById($sprintId);
		if ($sprint->isEmpty() || !$this->canReadGroupTasks($sprint->getGroupId()))
		{
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_DEADLINE_ERROR'),
				$sprintService->getErrors()
			);

			return null;
		}

		if ($dateStart)
		{
			$sprint->setDateStart(DateTime::createFromTimestamp($dateStart));
		}
		if ($dateEnd)
		{
			$sprint->setDateEnd(DateTime::createFromTimestamp($dateEnd));
		}
		$sprint->setModifiedBy(Util\User::getId());

		$sprintService->changeSprint($sprint, $pushService);

		if ($sprintService->getErrors())
		{
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SPRINT_UPDATE_DEADLINE_ERROR'),
				$sprintService->getErrors()
			);

			return null;
		}

		return $sprintService->getSprintData($sprintService->getSprintById($sprintId));
	}

	public function getSprintCompletedItemsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$inputSprintId = (is_numeric($post['sprintId'] ?? null) ? (int) $post['sprintId'] : 0);

		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($inputSprintId);
		if ($sprint->isEmpty())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'));

			return null;
		}

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

		$sprintItemIds = $itemService->getItemIdsBySourceIds($finishedTaskIds, [$sprint->getId()]);
		if ($itemService->getErrors())
		{
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SPRINT_GET_COMPLETED_ITEMS_ERROR'),
				$itemService->getErrors()
			);
			return null;
		}

		$items = $this->getItemsData($groupId, $sprintItemIds, $itemService, $taskService, $userService);

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

	public function removeSprintAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

		$sprintId = (is_numeric($post['sprintId'] ?? null) ? (int) $post['sprintId'] : 0);
		$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);

		$sprintService = new SprintService();
		$itemService = new ItemService();
		$backlogService = new BacklogService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = $sprintService->getSprintById($sprintId);
		if ($sprint->isEmpty() || !$sprint->isPlannedSprint() || $sprint->getGroupId() !== $groupId)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_REMOVE_ERROR'));

			return null;
		}

		$backlog = $backlogService->getBacklogByGroupId($sprint->getGroupId());

		$itemService->moveItemsToEntity(
			$itemService->getItemIdsByEntityId($sprint->getId()),
			$backlog->getId()
		);
		if ($itemService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SPRINT_REMOVE_ERROR'), $itemService->getErrors());

			return null;
		}

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

	public function updateItemSortAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$targetEntityId = (is_numeric($post['entityId'] ?? null) ? (int) $post['entityId'] : 0);
		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);

		$entityService = new EntityService();
		$itemService = new ItemService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);
		$kanbanService = new KanbanService();
		$taskService = new TaskService($this->userId);

		$targetEntity = null;
		if ($targetEntityId)
		{
			$targetEntity = $entityService->getEntityById($targetEntityId);
			if (
				($targetEntity->isEmpty() || $targetEntity->getGroupId() !== $groupId)
				|| (!$targetEntity->isEmpty() && !$this->canReadGroupTasks($targetEntity->getGroupId()))
			)
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));

				return null;
			}
		}

		if ($targetEntity)
		{
			$items = $itemIds ? $itemService->getItemsByIds($itemIds) : [];
			foreach ($items as $item)
			{
				if ($item->getEntityId() !== $targetEntity->getId())
				{
					$sourceEntity = $entityService->getEntityById($item->getEntityId());

					$taskId = $item->getSourceId();
					$subTaskIds = $taskService->getSubTaskIds($groupId, $taskId);
					$idsToMove = array_merge([$taskId], $subTaskIds);
					$itemIds = $itemService->getItemIdsBySourceIds($idsToMove);

					$itemService->updateEntityIdToItems($targetEntity->getId(), $itemIds);

					if ($targetEntity->isActiveSprint())
					{
						$kanbanService->addTasksToKanban($targetEntity->getId(), [$taskId]);
						$kanbanService->addTasksToKanban($targetEntity->getId(), $subTaskIds);
					}

					if (!$sourceEntity->isEmpty() && $sourceEntity->isActiveSprint())
					{
						$kanbanService->removeTasksFromKanban($sourceEntity->getId(), $idsToMove);
					}

					if($sourceEntity->getEntityType() !== $targetEntity->getEntityType())
					{
						foreach ($idsToMove as $taskId)
						{
							$taskService->addTaskMovingToLog($taskId, $groupId);
						}
					}
				}
			}
		}

		if ($sortInfo)
		{
			$itemService->sortItems($this->prepareSortInfo($groupId, $sortInfo), $pushService);
		}

		return '';
	}

	public function updateItemAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$itemId = (is_numeric($post['itemId'] ?? null) ? (int) $post['itemId'] : 0);
		$name = (is_string($post['name'] ?? null) ? $post['name'] : '');
		$storyPoints = (is_string($post['storyPoints'] ?? null) ? $post['storyPoints'] : null);
		$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);

		$itemService = new ItemService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$item = $itemService->getItemById($itemId);
		if ($item->isEmpty())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'));

			return null;
		}

		if (strlen($name) > 0)
		{
			if (
				!TaskAccessController::can(
					$this->userId,
					ActionDictionary::ACTION_TASK_EDIT,
					$item->getSourceId()
				)
			)
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'));

				return null;
			}

			$userId = Util\User::getId();
			$taskService = new TaskService($userId, $this->application);
			$taskService->changeTask($item->getSourceId(), [
				'TITLE' => $name
			]);
			if ($taskService->getErrors())
			{
				$this->setError(
					Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'),
					$taskService->getErrors()
				);

				return null;
			}
		}

		if ($storyPoints !== null)
		{
			$item->setStoryPoints($storyPoints);

			$itemService->changeItem($item, $pushService);
		}

		if ($sortInfo)
		{
			$itemService->sortItems($this->prepareSortInfo($groupId, $sortInfo), $pushService);
		}

		if ($itemService->getErrors())
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_UPDATE_ERROR'), $itemService->getErrors());

			return null;
		}

		return '';
	}

	public function removeItemsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

		$userId = (int) Util\User::getId();

		$itemIds = (is_array($post['itemIds'] ?? null) ? $post['itemIds'] : []);
		$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);

		$itemService = new ItemService();

		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$taskService = new TaskService($userId);

		foreach ($itemService->getItemsByIds($itemIds) as $item)
		{
			$taskService->removeTask($item->getSourceId());

			if ($taskService->getErrors())
			{
				$this->setError(Loc::getMessage('TASKS_SCRUM_ITEM_REMOVE_ERROR'), $taskService->getErrors());

				return null;
			}

			(new CacheService($item->getSourceId(), CacheService::ITEM_TASKS))->clean();

			$subTaskIds = $taskService->getSubTaskIds(
				$this->arParams['GROUP_ID'],
				$item->getSourceId(),
				false
			);
			foreach ($subTaskIds as $subTaskId)
			{
				(new CacheService($subTaskId, CacheService::ITEM_TASKS))->clean();
			}
		}

		if ($sortInfo)
		{
			$itemService->sortItems($this->prepareSortInfo($groupId, $sortInfo), $pushService);
		}

		return '';
	}

	public function updateSprintSortAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');

		$sortInfo = (is_array($post['sortInfo'] ?? null) ? $post['sortInfo'] : []);

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

	public function changeTaskResponsibleAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$taskId = (is_numeric($post['sourceId'] ?? null) ? (int) $post['sourceId'] : 0);
		$responsible = (is_array($post['responsible'] ?? null) ? $post['responsible'] : []);
		$responsibleId = (is_numeric($responsible['id'] ?? null) ? (int) $responsible['id'] : 0);

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

	public function getAllUsedItemBorderColorsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int)Util\User::getId();

		$entityIds = (is_array($post['entityIds'] ?? null) ? $post['entityIds'] : []);

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

	public function updateBorderColorToLinkedItemsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int) Util\User::getId();

		$items = (is_array($post['items'] ?? null) ? $post['items'] : []);

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

	public function getSubTaskItemsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = (int) Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$entityId = (is_numeric($post['entityId'] ?? null) ? (int) $post['entityId'] : 0);
		$taskId = (is_numeric($post['taskId'] ?? null) ? (int) $post['taskId'] : 0);

		$entityService = new EntityService();
		$kanbanService = new KanbanService();
		$taskService = new TaskService($this->userId);

		$entity = $entityService->getEntityById($entityId);
		if ($entity->isEmpty() || $entity->getGroupId() !== $groupId)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));

			return null;
		}

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
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'),
				$taskService->getErrors()
			);

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
			$itemsData[] = $this->getItemsData(
				$groupId,
				[$item->getId()],
				$itemService,
				$taskService,
				$userService
			)[0];
		}

		return $itemsData;
	}

	public function getItemsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$entityId = (is_numeric($post['entityId'] ?? null) ? (int) $post['entityId'] : 0);
		$pageNumber = (is_numeric($post['pageNumber'] ?? null) ? (int) $post['pageNumber'] : 1);
		$pageSize = (is_numeric($post['pageSize'] ?? null) ? (int) $post['pageSize'] : 1);
		$withoutNav = (isset($post['withoutNav']) && $post['withoutNav'] === 'Y');

		$entityService = new EntityService();

		$entity = $entityService->getEntityById($entityId);
		if ($entity->isEmpty())
		{
			return [];
		}

		if ($entity->getGroupId() !== $groupId)
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));

			return null;
		}

		$nav = $withoutNav ? null : $this->getNavToItems($entityId, $pageNumber, $pageSize);

		return $this->getEntityItems($groupId, [$entityId], $nav);
	}

	public function getEntityCountersAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$entityIds = (is_array($post['entityIds'] ?? null) ? $post['entityIds'] : []);

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

				$entityCounters = $entityService->getCounters($groupId, $entity->getId(), $taskService, false);
			}
			else if ($entity->isPlannedSprint())
			{
				$entityCounters = $entityService->getCounters($groupId, $entity->getId(), $taskService);
			}
			else if ($entity->isCompletedSprint())
			{
				$entityCounters = $entityService->getCounters($groupId, $entity->getId(), $taskService, false);
			}
			else
			{
				$entityCounters = $entityService->getCounters($groupId, $entity->getId(), $taskService);
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

	public function getCompletedSprintsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$pageNumber = (is_numeric($post['pageNumber'] ?? null) ? (int) $post['pageNumber'] : 1);

		$nav = $this->getNavToCompletedSprints($pageNumber);

		$entityService = new EntityService();
		$sprintService = new SprintService($this->userId);
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$completedSprints = $sprintService->getCompletedSprints($groupId, $nav, $itemService);
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
				$kanbanService
			);
		}

		return $sprints;
	}

	public function getCompletedSprintsStatsAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();
		$groupId = (int) $this->arParams['GROUP_ID'];

		$numberSprints = 0;
		$averageNumberTasks = 0;
		$averageNumberStoryPoints = 0;
		$averagePercentageCompletion = 0;

		$sprintService = new SprintService();
		$kanbanService = new KanbanService();
		$itemService = new ItemService();
		$storyPointsService = new StoryPoints();

		$sprints = $sprintService->getCompletedSprints($groupId);

		$numberTasks = [];
		$numberStoryPoints = [];
		$percentageCompletion = [];

		foreach ($sprints as $sprint)
		{
			$numberSprints++;

			$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());
			$uncompletedTaskIds = $kanbanService->getUnfinishedTaskIdsInSprint($sprint->getId());
			$taskIds = array_merge($completedTaskIds, $uncompletedTaskIds);

			$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($taskIds);
			$itemsCompletedStoryPoints = $itemService->getItemsStoryPointsBySourceId($completedTaskIds);

			$sumStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsStoryPoints);
			$sumCompletedStoryPoints = $storyPointsService->calculateSumStoryPoints($itemsCompletedStoryPoints);

			$numberTasks[$sprint->getId()] = count($taskIds);
			$numberStoryPoints[$sprint->getId()] = $sumStoryPoints;
			if ($sumStoryPoints)
			{
				$percentageCompletion[$sprint->getId()] = round($sumCompletedStoryPoints * 100 / $sumStoryPoints);
			}
		}

		if ($numberTasks)
		{
			$averageNumberTasks = array_sum(array_values($numberTasks)) / count($numberTasks);
		}
		if ($numberStoryPoints)
		{
			$averageNumberStoryPoints = array_sum(array_values($numberStoryPoints)) / count($numberStoryPoints);
		}
		if ($percentageCompletion)
		{
			$averagePercentageCompletion = array_sum(array_values($percentageCompletion))
				/ count($percentageCompletion)
			;
		}

		return [
			'numberSprints' => $numberSprints,
			'averageNumberTasks' => $averageNumberTasks,
			'averageNumberStoryPoints' => $averageNumberStoryPoints,
			'averagePercentageCompletion' => $averagePercentageCompletion,
		];
	}

	public function saveShortViewAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$isShortView = (isset($post['isShortView']) && $post['isShortView'] === 'Y');
		$groupId = (int) $this->arParams['GROUP_ID'];

		CUserOptions::setOption('tasks.scrum.'.$groupId, 'short_view', ($isShortView ? 'Y' : 'N'));

		return [];
	}

	public function saveSprintVisibilityAction(int $sprintId, string $isHidden): bool
	{
		$this->checkModules();

		$this->userId = Util\User::getId();

		$sprintService = new SprintService($this->userId);

		$sprint = $sprintService->getSprintById($sprintId);

		if ($isHidden === 'Y')
		{
			$sprint->hideContent($this->userId);
		}
		else
		{
			$sprint->showContent($this->userId);
		}

		return $sprintService->changeSprint($sprint);
	}

	public function saveDisplayPriorityAction()
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$value = (is_string($post['value'] ?? null) ? $post['value'] : 'sprint');
		$groupId = (int) $this->arParams['GROUP_ID'];

		$availableValues = ['backlog', 'sprint'];
		if (!in_array($value, $availableValues))
		{
			$this->setError(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));

			return null;
		}

		CUserOptions::setOption('tasks.scrum.'.$groupId, 'display_priority', $value);

		return [];
	}

	public function showLinkedTasksAction(): array
	{
		$this->checkModules();

		$request = Context::getCurrent()->getRequest();
		$post = $request->getPostList()->toArray();

		$this->debugMode = (isset($post['debugMode']) && $post['debugMode'] === 'Y');
		$this->userId = Util\User::getId();

		$taskId = (is_numeric($post['taskId'] ?? null) ? (int) $post['taskId'] : 0);
		$groupId = (int) $this->arParams['GROUP_ID'];

		$itemService = new ItemService($this->userId);

		$linkedTaskIds = $this->getLinkedTasks($taskId);
		if ($linkedTaskIds)
		{
			$itemIds = $itemService->getItemIdsBySourceIds($linkedTaskIds);

			return $this->getItemsData(
				$groupId,
				$itemIds,
				$itemService,
				new TaskService($this->userId),
				new UserService()
			);
		}

		return [];
	}

	public function getItemDataAction(array $itemIds, string $debugMode = 'N'): ?array
	{
		$this->checkModules();

		$this->debugMode = ($debugMode === 'Y');
		$this->userId = Util\User::getId();

		$groupId = (int) $this->arParams['GROUP_ID'];

		$itemService = new ItemService();
		$taskService = new TaskService($this->userId);
		$userService = new UserService();

		foreach ($itemIds as $key => $itemId)
		{
			$item = $itemService->getItemById($itemId);
			if (
				$item->isEmpty()
				|| !TaskAccessController::can(
					$this->userId,
					ActionDictionary::ACTION_TASK_READ,
					$item->getSourceId()
				)
			)
			{
				unset($itemIds[$key]);
			}
		}

		return $this->getItemsData(
			$groupId,
			$itemIds,
			$itemService,
			$taskService,
			$userService
		);
	}

	public function getSprintDataAction(int $sprintId, string $debugMode = 'N'): ?array
	{
		$this->checkModules();

		$this->debugMode = ($debugMode === 'Y');
		$this->userId = Util\User::getId();

		$sprintService = new SprintService($this->userId);

		$sprint = $sprintService->getSprintById($sprintId);
		if (
			$sprintService->getErrors()
			|| $sprint->isEmpty()
			|| !$this->canReadGroupTasks($sprint->getGroupId())
		)
		{
			$this->setError(
				Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'),
				$sprintService->getErrors()
			);

			return null;
		}

		$sprintViews = $this->getViewsInfo($sprint->getGroupId());
		$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);
		$entityService = new EntityService();
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$sprintData = $this->prepareSprintData(
			$sprint,
			$sprintViews,
			$cacheService,
			$entityService,
			$sprintService,
			$itemService,
			$kanbanService
		);

		$sprintData['pageNumberItems'] = 0;
		$sprintData['pageSize'] = 10;

		return $sprintData;
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

	private function isScrumEnabled(): bool
	{
		return (new Settings())->isToolAvailable(Settings::TOOLS['scrum']);
	}

	private function includePlanTemplate(int $groupId): void
	{
		$this->arResult['isShortView'] = $this->getShortViewState($groupId);
		$this->arResult['displayPriority'] = $this->getDisplayPriorityState($groupId);

		$taskService = new TaskService($this->userId, $this->application);

		$this->arResult['mandatoryExists'] = $taskService->mandatoryExists() ? 'Y' : 'N';

		$filterInstance = $taskService->getFilterInstance($groupId);

		$this->arResult['isExactSearchApplied'] = ($filterInstance->isExactSearchApplied() ? 'Y' : 'N');

		$this->arResult['filterInstance'] = $filterInstance;

		$userService = new UserService();

		$this->arResult['activeSprintId'] = 0;

		$responsibleId = $this->getDefaultResponsibleId($groupId);
		$this->arResult['defaultResponsible'] = $userService->getInfoAboutUsers([$responsibleId]);

		$this->arResult['counters'] = null;
		if ($taskService->hasAccessToCounters())
		{
			$this->arResult['counters'] = $this->getCounters($this->userId, $groupId, $filterInstance);
		}

		if ($this->getErrors())
		{
			$this->includeErrorTemplate(
				current($this->getErrors()),
				$this->getFirstErrorCode($this->getErrors())
			);

			return;
		}

		$entityService = new EntityService();

		$group = Workgroup::getById($groupId);

		$pageSize = 10;

		$this->arResult['defaultSprintDuration'] = $group->getDefaultSprintDuration();
		$this->arResult['pageSize'] = $pageSize;

		$backlog = $this->getBacklog($groupId);
		if ($backlog->isEmpty())
		{
			$backlog = $this->createNewBacklogForThisProject($groupId);
			$firstSprint = $this->createNewSprintForThisProject($groupId);
		}

		$entityIdsOfCurrentGroup = $entityService->getEntityIds($groupId);

		$this->syncItemsWithTasks($backlog->getId(), $entityIdsOfCurrentGroup, $this->userId, $groupId);

		$this->arResult['sprints'] = $this->getSprints(
			$groupId,
			$pageSize,
			$this->arResult['isExactSearchApplied'] === 'Y' ? null : $this->getNavToCompletedSprints(1, 1)
		);

		$backlogItems = $this->getEntityItems(
			$backlog->getGroupId(),
			[$backlog->getId()],
			$this->getNavToItems($backlog->getId(), 1, $pageSize)
		);

		$entityCounters = $entityService->getCounters($groupId, $backlog->getId(), $taskService);

		$this->arResult['backlog'] = [
			'id' => $backlog->getId(),
			'storyPoints' => $entityCounters['storyPoints'],
			'numberTasks' => $entityCounters['countTotal'],
			'items' => $backlogItems,
			'pageNumberItems' => 1,
			'pageSize' => $pageSize,
			'isShortView' => $this->arResult['isShortView'],
			'mandatoryExists' => $this->arResult['mandatoryExists'],
			'isExactSearchApplied' => $this->arResult['isExactSearchApplied'],
		];

		foreach ($this->arResult['sprints'] as $key => $sprintData)
		{
			$this->arResult['sprints'][$key]['isShortView'] = $this->arResult['isShortView'];
			$this->arResult['sprints'][$key]['mandatoryExists'] = $this->arResult['mandatoryExists'];
		}

		$info = $backlog->getInfo();

		$typeService = new TypeService();

		if ($typeService->isEmpty($backlog->getId()) && !$info->isTypesGenerated())
		{
			$productType = new TypeForm();
			$productType->setEntityId($backlog->getId());
			$productType->setName(Loc::getMessage('TASKS_SCRUM_TYPE_PRODUCT_NAME'));
			$productType->setSort(1);
			$productType->setDodRequired('Y');

			$technicalType = new TypeForm();
			$technicalType->setEntityId($backlog->getId());
			$technicalType->setName(Loc::getMessage('TASKS_SCRUM_TYPE_TECHNICAL_NAME'));
			$technicalType->setSort(2);

			$defaultParticipantsCodes = ['SG' . $groupId . '_E'];

			$productType = $typeService->createType($productType);

			$productType->setParticipantsCodes($defaultParticipantsCodes);
			$typeService->saveParticipants($productType);

			$technicalType = $typeService->createType($technicalType);
			$typeService->saveParticipants($technicalType);

			$technicalType->setParticipantsCodes($defaultParticipantsCodes);

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

		// todo tmp
		if (false && !$info->isTypesParticipantsGenerated())
		{
			$defaultParticipantsCodes = ['SG' . $groupId . '_E'];

			foreach ($typeService->getTypes($backlog->getId()) as $type)
			{
				if (empty($type->getParticipantsCodes()))
				{
					$type->setParticipantsCodes($defaultParticipantsCodes);

					$typeService->saveParticipants($type);
				}
			}

			$this->updateTypeParticipantsCreationStatus($backlog);
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

		$this->arResult['filterInstance'] = $taskService->getFilterInstance($groupId, 'active');

		$this->arResult['activeSprintId'] = 0;

		$sprintService = new SprintService();

		$sprint = $sprintService->getActiveSprintByGroupId($groupId);

		$this->arResult['taskLimitExceeded'] = false;
		$this->arResult['canUseAutomation'] = false;
		$this->arResult['canCompleteSprint'] = false;
		$this->arResult['isAutomationEnabled'] = false;

		if ($sprint->isActiveSprint())
		{
			$this->arResult['activeSprintId'] = ($sprintService->getErrors() ? 0 : $sprint->getId());

			$this->restoreStagesLastCompletedSprint($this->userId, $groupId, $sprint->getId());

			$this->arResult['taskLimitExceeded'] = Bitrix24Restriction\Limit\TaskLimit::isLimitExceeded();
			$this->arResult['canUseAutomation'] = Factory::canUseAutomation();
			$this->arResult['isAutomationEnabled'] = Factory::isAutomationEnabled();

			$this->arResult['canCompleteSprint'] = $sprintService->canCompleteSprint($this->userId, $groupId);

			$kanbanService = new KanbanService();

			$this->arResult['orderNewTask'] = $kanbanService->getKanbanSortValue($groupId);
		}

		if ($this->getErrors())
		{
			$this->includeErrorTemplate(
				current($this->getErrors()),
				$this->getFirstErrorCode($this->getErrors())
			);

			return;
		}

		$this->includeComponentTemplate('active_sprint');
	}

	private function includeCompletedSprintTemplate(int $groupId, int $sprintId): void
	{
		$taskService = new TaskService($this->userId, $this->application);
		$filterInstance = $taskService->getFilterInstance($groupId, 'complete');

		$this->arResult['filterInstance'] = $filterInstance;

		$entityService = new EntityService();
		$sprintService = new SprintService($this->userId);
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

		$sprintViews = $this->arResult['views'];

		if ($completedSprint->isEmpty())
		{
			$completedSprintData = [];
		}
		else
		{
			$completedSprintData = $sprintService->getSprintData($completedSprint);

			$sprintViews['completedSprint']['url'] = $sprintViews['completedSprint']['url']
				. '&sprintId=' . $completedSprint->getId();
			$completedSprintData['views'] = $sprintViews;
		}

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
			$title = Loc::getMessage('TASKS_SCRUM_TITLE');
			$this->application->setTitle($title);

			if (
				(int) $this->arParams['GROUP_ID'] > 0
				&& method_exists(ComponentHelper::class, 'getWorkgroupPageTitle')
			)
			{
				$this->application->SetPageProperty(
					'title',
					ComponentHelper::getWorkgroupPageTitle([
						'WORKGROUP_ID' => (int) $this->arParams['GROUP_ID'],
						'TITLE' => $title
					])
				);
			}
		}
	}

	/**
	 * @param int $groupId
	 * @return EntityForm
	 * @throws SystemException
	 */
	private function getBacklog(int $groupId): EntityForm
	{
		$backlogService = new BacklogService();

		$backlog = $backlogService->getBacklogByGroupId($groupId);

		if ($backlogService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR'));
		}

		return $backlog;
	}

	/**
	 * @param int $groupId
	 * @return EntityForm
	 * @throws SystemException
	 */
	private function createNewBacklogForThisProject(int $groupId): EntityForm
	{
		$backlogService = new BacklogService();

		$backlog = new EntityForm();
		$backlog->setGroupId($groupId);
		$backlog->setCreatedBy($this->userId);

		$backlog = $backlogService->createBacklog($backlog);

		if ($backlogService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_ADD_BACKLOG'));
		}

		return $backlog;
	}

	private function createNewSprintForThisProject(int $groupId): EntityForm
	{
		$sprintService = new SprintService();

		$sprint = new EntityForm();

		$sprint->setGroupId($groupId);
		$sprint->setName(Loc::getMessage('TASKS_SCRUM_SPRINT_NAME', ['%s' => 1]));
		$sprint->setSort(1);
		$sprint->setCreatedBy($this->userId);
		$sprint->setModifiedBy($this->userId);
		$sprint->setDateStart(DateTime::createFromTimestamp(time()));
		$sprint->setDateEnd(DateTime::createFromTimestamp(time() + $this->arResult['defaultSprintDuration']));

		$sprint = $sprintService->createSprint($sprint);

		if ($sprintService->getErrors())
		{
			throw new SystemException(Loc::getMessage('TASKS_SCRUM_SYSTEM_ERROR_ADD_SPRINT'));
		}

		return $sprint;
	}

	private function updateTypeCreationStatus(EntityForm $backlog): void
	{
		$backlogService = new BacklogService();

		$info = $backlog->getInfo();
		$info->setTypesGenerated('Y');

		$backlog->setInfo($info);

		$backlogService->changeBacklog($backlog);
	}

	private function updateTypeParticipantsCreationStatus(EntityForm $backlog): void
	{
		$backlogService = new BacklogService();

		$info = $backlog->getInfo();
		$info->setTypesParticipantsGenerated('Y');

		$backlog->setInfo($info);

		$backlogService->changeBacklog($backlog);
	}

	private function getSprints(int $groupId, int $pageSize, PageNavigation $sprintNav = null): array
	{
		$entityService = new EntityService();
		$sprintService = new SprintService($this->userId);
		$itemService = new ItemService();
		$kanbanService = new KanbanService();

		$uncompletedSprints = $sprintService->getUncompletedSprints($groupId);
		$completedSprints = $sprintService->getCompletedSprints($groupId, $sprintNav);

		$listSprints = array_merge($uncompletedSprints, $completedSprints);

		$sprints = [];

		$sprintViews = $this->getViewsInfo($groupId);

		foreach ($listSprints as $sprint)
		{
			$cacheService = new CacheService($sprint->getId(), CacheService::COMPLETED_SPRINT);

			$sprints[$sprint->getId()] = $this->prepareSprintData(
				$sprint,
				$sprintViews,
				$cacheService,
				$entityService,
				$sprintService,
				$itemService,
				$kanbanService
			);
		}

		$sprintItems = $this->getEntityItems(
			$groupId,
			array_keys($sprints),
			$this->getNavToItems('sprints', 1, $pageSize * count($sprints))
		);

		return $this->addItemsToSprintsData($sprintItems, $sprints, $pageSize);
	}

	private function prepareSprintData(
		EntityForm $sprint,
		array $sprintViews,
		CacheService $cacheService,
		EntityService $entityService,
		SprintService $sprintService,
		ItemService $itemService,
		KanbanService $kanbanService
	): array
	{
		if (
			$sprint->isCompletedSprint()
			&& isset($this->arResult['isExactSearchApplied'])
			&& $this->arResult['isExactSearchApplied'] === 'N'
		)
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

		$sprintData['items'] = [];

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
				$sprint->getGroupId(),
				$sprint->getId(),
				new TaskService($this->userId),
				(!$sprint->isActiveSprint())
			);
		}

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
		$sprintData['pageNumberItems'] = 1;
		$sprintData['pageSize'] = $this->arResult['pageSize'] ?? 0;
		$sprintData['isExactSearchApplied'] = $this->arResult['isExactSearchApplied'] ?? null;

		if (
			$sprint->isCompletedSprint()
			&& isset($this->arResult['isExactSearchApplied'])
			&& $this->arResult['isExactSearchApplied'] === 'N'
		)
		{
			$cacheService->start();
			$cacheService->end($sprintData);
		}

		return $sprintData;
	}

	private function addItemsToSprintsData(array $sprintItems, array $sprintsData, int $pageSize): array
	{
		foreach ($sprintItems as $sprintItem)
		{
			if (array_key_exists($sprintItem['entityId'], $sprintsData))
			{
				$sprintsData[$sprintItem['entityId']]['items'][] = $sprintItem;
			}
		}

		foreach ($sprintsData as &$sprintData)
		{
			$pageNumber = (int) ceil(count($sprintData['items']) / $pageSize);
			$sprintData['pageNumberItems'] = $pageNumber ?: 1;
		}

		return array_values($sprintsData);
	}

	private function getViewsInfo(int $groupId): array
	{
		$request = Context::getCurrent()->getRequest();

		if ($request->isAjaxRequest())
		{
			$uri = new Uri(str_replace('#group_id#', $groupId, $this->arParams['PATH_TO_GROUP_TASKS']));
		}
		elseif(
			stripos($request->getRequestUri(), '/task/view/') > 0
			|| stripos($request->getRequestUri(), '/task/edit/') > 0
		)
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

		$viewHelper = new ViewHelper($this->getSiteId());

		return [
			'plan' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_PLAN'),
				'url' => $planningUrl,
				'active' => ($viewHelper->getActiveView($groupId) == 'plan')
			],
			'activeSprint' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_SPRINT'),
				'url' => $activeSprintUrl,
				'active' => ($viewHelper->getActiveView($groupId) == 'active_sprint')
			],
			'completedSprint' => [
				'name' => Loc::getMessage('TASKS_SCRUM_TAB_COMPLETED_SPRINT'),
				'url' => $completedSprintUrl,
				'active' => ($viewHelper->getActiveView($groupId) == 'completed_sprint')
			],
		];
	}

	private function getCultureInfo(): array
	{
		$culture = Context::getCurrent()->getCulture();

		return [
			'dayMonthFormat' => stripslashes($culture->getDayMonthFormat()),
			'longDateFormat' => stripslashes($culture->getLongDateFormat()),
			'shortTimeFormat' => stripslashes($culture->getShortTimeFormat()),
		];
	}

	private function getShortViewState(int $groupId)
	{
		return CUserOptions::getOption('tasks.scrum.'.$groupId, 'short_view', 'Y');
	}

	private function getDisplayPriorityState(int $groupId)
	{
		return CUserOptions::getOption('tasks.scrum.'.$groupId, 'display_priority', 'sprint');
	}

	private function createSprint(SprintService $sprintService, array $fields): EntityForm
	{
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$sprint = new EntityForm();

		$sprint->setGroupId($fields['groupId']);
		$sprint->setTmpId($fields['tmpId']);
		$sprint->setName($fields['name']);
		$sprint->setSort($fields['sort']);
		$sprint->setStatus($fields['status']);
		$sprint->setCreatedBy($fields['userId']);
		$sprint->setModifiedBy($fields['userId']);
		$sprint->setDateStart(DateTime::createFromTimestamp($fields['dateStart']));
		$sprint->setDateEnd(DateTime::createFromTimestamp($fields['dateEnd']));

		return $sprintService->createSprint($sprint, $pushService);
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

	private function getDefaultResponsibleId(int $groupId): int
	{
		if ($group = Workgroup::getById($groupId))
		{
			$scrumTaskResponsible = $group->getScrumTaskResponsible();
			return ($scrumTaskResponsible == 'A' ? $this->userId : $group->getScrumMaster());
		}

		return $this->userId;
	}

	private function getEntityItems(
		int $groupId,
		array $entityIds,
		PageNavigation $navigation = null
	): array
	{
		$userService = new UserService();
		$itemService = new ItemService();
		$taskService = new TaskService($this->userId, $this->application);
		$taskService->setOwnerId($this->arParams['OWNER_ID']);

		$filterInstance = $taskService->getFilterInstance($groupId);
		$filter = $taskService->getFilter($filterInstance);
		$taskIds = $taskService->getTaskIdsByFilter($filter, $navigation, $entityIds);
		$itemIds = $itemService->getItemIdsBySourceIds($taskIds, $entityIds);

		return $this->getItemsData($groupId, $itemIds, $itemService, $taskService, $userService);
	}

	private function getCounters(int $userId, int $groupId, $filterInstance): array
	{
		$counterInstance = Counter::getInstance($userId);
		$filterRole = $this->getFilterRole($filterInstance);

		return $counterInstance->getCounters($filterRole, $groupId);
	}

	private function getItemsData(
		int $groupId,
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

		$epicService = new EpicService();

		foreach ($items as $item)
		{
			if (!$item->getEpicId())
			{
				continue;
			}

			$taskId = $item->getSourceId();

			$cacheService = new CacheService($item->getEpicId(), CacheService::EPICS);

			if ($cacheService->init())
			{
				$itemsData[$taskId]['epic'] = $cacheService->getData();
			}
			else
			{
				$epic = $epicService->getEpic($item->getEpicId());

				$epicData = $epic->getId() ? $epic->toArray() : [];

				$cacheService->start();
				$cacheService->end($epicData);

				$itemsData[$taskId]['epic'] = $epicData;
			}
		}

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

			$itemsData = $taskService->getItemsDynamicData($groupId, $taskIdsForDynamicData, $itemsData);

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

			(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($this->userId, $taskIds);

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

		$pathToTask = str_replace('#action#', 'view', $this->arParams['PATH_TO_GROUP_TASKS_TASK']);
		$pathToTask = str_replace('#group_id#', $this->arParams['GROUP_ID'], $pathToTask);

		$itemData['pathToTask'] = $pathToTask;

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
			if (($itemData['isSubTask'] ?? null) === 'Y')
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

		$viewHelper = new ViewHelper($this->getSiteId());
		if ($viewHelper->getActiveView($groupId) === 'plan')
		{
			$pullService->subscribeToEntityActions();
		}

		$pullService->subscribeToItemActions();
	}

	private function syncItemsWithTasks(
		int $backlogId,
		array $entityIdsOfCurrentGroup,
		int $userId,
		int $groupId
	): void
	{
		$taskService = new TaskService($userId);

		$currentTaskIds = $taskService->getTaskIds($groupId);
		if ($taskService->getErrors())
		{
			return;
		}

		$itemService = new ItemService();

		$itemIds = $itemService->getItemIdsBySourceIds($currentTaskIds, $entityIdsOfCurrentGroup);

		if (count($currentTaskIds) > count($itemIds))
		{
			foreach ($currentTaskIds as $taskId)
			{
				$item = $itemService->getItemBySourceId($taskId);
				if (!$itemService->getErrors())
				{
					if ($item->isEmpty())
					{
						$scrumItem = new ItemForm();

						$scrumItem->setCreatedBy($userId);
						$scrumItem->setEntityId($backlogId);
						$scrumItem->setSourceId($taskId);

						$itemService->createTaskItem($scrumItem);
					}
					else
					{
						if (!in_array($item->getEntityId(), $entityIdsOfCurrentGroup, true))
						{
							$item->setEntityId($backlogId);

							$itemService->changeItem($item);
						}
					}
				}
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

	private function getNavToItems(?string $navKey, int $pageNumber = 1, int $pageSize = 10): PageNavigation
	{
		$nav = new PageNavigation('entity-items-' . $navKey);

		$nav->setPageSize($pageSize);
		$nav->setCurrentPage($pageNumber);

		return $nav;
	}

	private function getNavToCompletedSprints(int $pageNumber = 1, int $pageSize = 10): PageNavigation
	{
		$nav = new PageNavigation('completed-sprints');

		$nav->setPageSize($pageSize);
		$nav->setCurrentPage($pageNumber);

		return $nav;
	}

	private function getLinkedTasks(int $taskId, array &$linkedTaskIds = []): array
	{
		if (!in_array($taskId, $linkedTaskIds))
		{
			$linkedTaskIds[] = $taskId;

			$taskService = new TaskService($this->userId, $this->application);
			$linkedTasks = $taskService->getLinkedTasks($taskId);

			foreach ($linkedTasks as $linkedTaskId)
			{
				$this->getLinkedTasks($linkedTaskId, $linkedTaskIds);
			}
		}

		return $linkedTaskIds;
	}

	private function setBorderColor(ItemForm $item, string $color): ItemForm
	{
		$info = $item->getInfo();

		$info->setBorderColor($color);

		$item->setInfo($info);

		return $item;
	}

	/**
	 * Deletes tasks that are trying to move to another project.
	 *
	 * @param int $groupId Group id.
	 * @param array $sortInfo List request data.
	 * @return array
	 */
	private function prepareSortInfo(int $groupId, array $sortInfo): array
	{
		$entityIds = [];
		foreach($sortInfo as $itemId => $info)
		{
			$updatedItemId = (is_numeric($info['updatedItemId'] ?? null) ? (int) $info['updatedItemId'] : 0);
			$entityId = (is_numeric($info['entityId'] ?? null) ? (int) $info['entityId'] : 0);
			$itemId = (is_numeric($itemId) ? (int) $itemId : 0);

			if ($itemId && $updatedItemId && $entityId)
			{
				if (!isset($entityIds[$entityId]))
				{
					$entityIds[$entityId] = [];
				}
				$entityIds[$entityId][] = $itemId;
			}
		}

		if ($entityIds)
		{
			$entityService = new EntityService();

			$queryObject = $entityService->getList(null, ['ID' => array_keys($entityIds)], ['ID', 'GROUP_ID']);
			while ($entityData = $queryObject->fetch())
			{
				$targetEntityId = (int) $entityData['ID'];
				$targetGroupId = (int) $entityData['GROUP_ID'];
				if ($targetGroupId !== $groupId)
				{
					foreach ($entityIds[$targetEntityId] as $itemId)
					{
						unset($sortInfo[$itemId]);
					}
				}
			}
		}

		return $sortInfo;
	}

	private function restoreStagesLastCompletedSprint(int $userId, int $groupId, int $sprintId): void
	{
		$sprintService = new SprintService();

		$sprint = $sprintService->getSprintById($sprintId);
		if (
			$sprint->isEmpty()
			|| !$sprint->isActiveSprint()
			|| $sprint->getGroupId() !== $groupId
		)
		{
			return;
		}

		if (!$sprintService->canStartSprint($userId, $groupId))
		{
			return;
		}

		if ($sprint->getInfo()->sprintStagesRecoveryStatusIsVerified())
		{
			return;
		}

		$sprint->getInfo()->setSprintStagesRecoveryStatusToChecked();
		if (!$sprintService->changeSprint($sprint))
		{
			return;
		}

		$kanbanService = new KanbanService();

		$lastSprintId = $kanbanService->getLastCompletedSprintIdSameGroup($sprint->getId());
		if (!$lastSprintId)
		{
			return;
		}

		if (!$kanbanService->hasDifferencesBetweenTwoSprints($sprint->getId(), $lastSprintId))
		{
			return;
		}

		$robotService = (Loader::includeModule('bizproc') ? new RobotService() : null);
		if ($robotService)
		{
			$currentSprintHasRobots = $robotService->hasRobots(
				$groupId,
				$kanbanService->getSprintStageIds($sprint->getId())
			);
			if ($currentSprintHasRobots)
			{
				return;
			}
		}

		$sprint->setStatus(EntityForm::SPRINT_PLANNED);
		$sprint->getInfo()->setSprintStagesRecoveryStatusToCompleted();
		if (!$sprintService->changeSprint($sprint))
		{
			return;
		}

		$kanbanService->removeStages($sprint->getId());

		$taskService = new TaskService($userId);
		$itemService = new ItemService();
		$backlogService = new BacklogService();

		$sprintService->startSprint(
			$sprint,
			$taskService,
			$kanbanService,
			$itemService,
			$backlogService,
			$robotService
		);
	}
}