<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Access\AccessCacheLoader;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TagAccessController;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Pull\PushService;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\CollabRegistry;
use Bitrix\Tasks\Internals\Registry\TagRegistry;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Provider\Tag\TagList;
use Bitrix\Tasks\Provider\Tag\TagQuery;
use Bitrix\Tasks\Provider\TaskProvider;
use Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

class TasksTagList extends CBitrixComponent implements Controllerable, Errorable
{
	public const GRID_ID = 'tags_list';
	public const FILTER_ID = 'tags_list_filter';

	public const TAGS_PAGE_SIZE = 10;
	public const TASKS_PAGE_SIZE = 10;
	private Tag $tagService;
	private TagList $list;
	private ErrorCollection $errorCollection;
	private int $userId;
	private int $groupId;
	private bool $canUseGridActions = true;

	public function configureActions(): array
	{
		return [];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	private function init(): void
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$this->setGroupId();
		$this->setUserId();
		$this->setTagService();
		if (!empty($this->groupId))
		{
			$this->canUseGridActions = $this->canUseGridActions($this->groupId);
		}
		$this->errorCollection = new ErrorCollection();
		$this->list = new TagList();
	}

	private function setTagService(): void
	{
		$this->tagService = new Tag($this->userId);
	}

	private function setGroupId(): void
	{
		$this->groupId = (int)$this->request->get('GROUP_ID');
	}

	private function setUserId(): void
	{
		$this->userId = (int)CurrentUser::get()->getId();
	}

	public function getErrorByCode($code)
	{
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	private function addForbiddenError(): void
	{
		$this->errorCollection->setError(
			new Error(Loc::getMessage('TAG_ACTION_NOT_ALLOWED'), 'ACTION_NOT_ALLOWED.RESTRICTED')
		);
	}

	public function deleteTagAction(int $tagId, int $groupId): ?array
	{
		if ($tagId === 0)
		{
			return null;
		}

		if (
			!TagAccessController::can($this->userId, ActionDictionary::ACTION_TAG_DELETE, $tagId, ['GROUP_ID' => $groupId])
		)
		{
			$this->addForbiddenError();
			return [];
		}

		$this->tagService->delete([$tagId]);

		$members = $this->getGroupMembers($groupId);
		$members[] = $this->userId;
		$recipients = array_unique($members);

		PushService::addEvent($recipients, [
			'module_id' => 'tasks',
			'command' => PushCommand::TAG_UPDATED,
			'params' => [
				'newTagName' => '',
				'oldTagName' => '',
				'groupId' => $groupId,
				'userId' => $this->userId,
			],
		]);

		return [];
	}

	public function getTaskTagsAction(int $taskId): array
	{
		if (empty($taskId))
		{
			return [];
		}
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$query = new TagQuery();
		$query
			->setSelect(['NAME'])
			->setWhere(['TASK_ID' => $taskId]);

		$collection = $this->list->getCollection($query);
		$names = [];
		foreach ($collection as $tagObject)
		{
			$names[] = $tagObject->getName();
		}

		return $names;
	}

	public function addTagAction(string $newTag, int $groupId, int $taskId): ?array
	{
		if (empty(trim($newTag)))
		{
			return null;
		}

		if ($taskId === 0)
		{
			return $this->addNew($newTag, $groupId);
		}

		$task = TaskRegistry::getInstance()->get($taskId);
		if (is_null($task))
		{
			return null;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
		{
			$this->addForbiddenError();
			return [
				'success' => false,
				'error' => Loc::getMessage('TAG_ACTION_NOT_ALLOWED'),
			];
		}

		$groupId = 0;
		$groupName = '';

		if (!is_null($task['GROUP_INFO']))
		{
			$groupId = $task['GROUP_INFO']['ID'];
			$groupName = $task['GROUP_INFO']['NAME'];
		}


		if ($this->tagService->isExists($newTag, $groupId, $taskId))
		{
			return [
				'success' => false,
				'error' => Loc::getMessage('TASKS_TAG_ALREADY_EXISTS'),
			];
		}


		$canReadGroupTags = TagAccessController::can(
			$this->userId,
			ActionDictionary::ACTION_GROUP_TAG_READ,
			null,
			['GROUP_ID' => $groupId]
		);

		if ($groupId > 0 && $canReadGroupTags)
		{
			$this->tagService->addTagToGroup($newTag, $groupId);
			$members = $this->getGroupMembers($groupId);
			$members[] = $this->userId;
			$recipients = array_unique($members);
			$owner = $groupName;
		}
		else
		{
			$this->tagService->addTagToUser($newTag);
			$recipients = $this->userId;
			$owner = CurrentUser::get()->getFormattedName();
		}

		PushService::addEvent($recipients, [
			'module_id' => 'tasks',
			'command' => PushCommand::TAG_ADDED,
			'params' => [
				'groupId' => $groupId,
			],
		]);

		return [
			'success' => true,
			'error' => '',
			'owner' => $owner,
		];
	}

	public function updateTagAction(int $tagId, string $newName, int $groupId): ?array
	{
		if ($tagId === 0)
		{
			return null;
		}

		if (empty($newName))
		{
			return [
				'success' => false,
				'error' => Loc::getMessage('TASKS_TAG_EMPTY_TAG_NAME'),
			];
		}
		if (
			!TagAccessController::can($this->userId, ActionDictionary::ACTION_TAG_EDIT, $tagId, ['GROUP_ID' => $groupId])
		)
		{
			$this->addForbiddenError();
			return [
				'success' => false,
				'error' => Loc::getMessage('TAG_ACTION_NOT_ALLOWED'),
			];
		}

		$query = new TagQuery();
		$query->setSelect(['NAME'])->setWhere(['ID' => $tagId]);
		$collection = $this->list->getCollection($query);

		$currentName = (string)$collection->getByPrimary($tagId)?->getName();
		if ($currentName === $newName)
		{
			return [
				'success' => false,
				'error' => '',
			];
		}
		if ($groupId > 0)
		{
			if ($this->tagService->isExistsByGroup($groupId, $newName))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TAG_IS_ALREADY_EXISTS'),
				];
			}

			$members = $this->getGroupMembers($groupId);
			$members[] = $this->userId;
			$recipients = array_unique($members);
		}
		else
		{
			if ($this->tagService->isExistsByUser($newName))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TAG_IS_ALREADY_EXISTS'),
				];
			}

			$recipients = $this->userId;
		}

		$this->tagService->edit($tagId, $newName);

		PushService::addEvent($recipients, [
			'module_id' => 'tasks',
			'command' => PushCommand::TAG_UPDATED,
			'params' => [
				'oldTagName' => $currentName,
				'newTagName' => $newName,
				'groupId' => $groupId,
			],
		]);

		return [
			'success' => true,
			'error' => '',
		];
	}

	public function deleteTagGroupAction(array $tags, int $groupId): void
	{
		if (empty($tags))
		{
			return;
		}

		(new AccessCacheLoader)->preloadTags($tags);

		$params = null;
		if (!empty($groupId))
		{
			$params = ['GROUP_ID' => $groupId];
		}
		$isSuccessfully = true;
		$names = [];

		foreach ($tags as $id)
		{
			if (!TagAccessController::can($this->userId, ActionDictionary::ACTION_TAG_DELETE, $id, $params))
			{
				$this->addForbiddenError();
				$isSuccessfully = false;
				break;
			}
			$names[] = TagRegistry::getInstance()->get($id)['NAME'];
		}

		if ($isSuccessfully)
		{
			$this->tagService->delete($tags);

			$members = $this->getGroupMembers($groupId);
			$members[] = $this->userId;
			$recipients = array_unique($members);

			PushService::addEvent($recipients, [
				'module_id' => 'tasks',
				'command' => PushCommand::TAG_UPDATED,
				'params' => [
					'oldTagsNames' => $names,
					'newTagName' => '',
					'groupId' => $groupId,
					'userId' => $this->userId,
				],
			]);
		}
	}

	private function getRows(): array
	{
		$searchRequest = (string)$this->getSearchRequest();
		$sortRequest = (string)$this->request->get('by');

		$nav = new PageNavigation('page');
		$nav
			->allowAllRecords(false)
			->setPageSize(self::TAGS_PAGE_SIZE)
			->initFromUri()
		;

		$query = LabelTable::query();
		$query
			->registerRuntimeField(
				'',
				new ReferenceField(
					'rel',
					TaskTagTable::getEntity(),
					[
						'=ref.TAG_ID' => 'this.ID',
					],
					[
						'join_type' => 'left',
					])
			);
		if (!empty($this->groupId))
		{
			$canReadGroupTags = TagAccessController::can(
				$this->userId,
				ActionDictionary::ACTION_GROUP_TAG_READ,
				null,
				['GROUP_ID' => $this->groupId]
			);

			if ($canReadGroupTags)
			{
				$query->setFilter(['=GROUP_ID' => $this->groupId]);
			}
			else
			{
				$query->setFilter(['=GROUP_ID' => 0]);
				$query->addFilter('USER_ID', 0);
			}
		}
		else
		{
			$query->setFilter(['=USER_ID' => $this->userId]);
		}
		$query
			->addSelect('ID')
			->addSelect('NAME')
			->setOffset($nav->getOffset())
			->setLimit($nav->getLimit())
			->addSelect(new ExpressionField('ID_COUNT', 'COUNT(TASK_ID)'))
			->countTotal(true);

		if (!empty($searchRequest))
		{
			$query->addFilter('NAME', '%' . trim($searchRequest) . '%');
		}
		if (empty($sortRequest))
		{
			$query->setOrder(['NAME' => 'asc']);
		}
		if ($sortRequest === 'NAME')
		{
			$order = $this->request->get('order');
			$query->setOrder(['NAME' => $order]);
		}
		if ($this->request->get('by') === 'COUNT')
		{
			$order = $this->request->get('order');
			$query->setOrder(['ID_COUNT' => $order]);
		}

		$tagRows = $query->exec();

		$nav->setRecordCount($tagRows->getCount());

		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
		$this->arResult['ENABLE_NEXT_PAGE'] = (
			($nav->getCurrentPage() * $nav->getPageSize() + 1) <= $nav->getRecordCount()
		);

		$res = $tagRows->fetchAll();
		$tags = [];
		foreach ($res as $tag)
		{
			$tags[] = [
				'ID' => $tag['ID'],
				'NAME' => $tag['NAME'],
				'COUNT' => $tag['ID_COUNT'],
			];
		}

		return $tags;
	}

	private function getFilterFields(): array
	{
		return [
			'tag_name' => [
				'id' => 'TAG_NAME',
				'name' => Loc::getMessage('TASKS_USER_TAGS_GRID_COLUMN_NAME'),
				'default' => true,
				'required' => true,
			],
		];
	}

	private function getSearchRequest(): ?string
	{
		$filterOptions = new Options(self::FILTER_ID);
		$filterData = $filterOptions->getFilter([]);

		$fromSearch = (string)($filterData['FIND'] ?? '');
		if (!empty($fromSearch))
		{
			return $fromSearch;
		}

		$fromField = (string)($filterData['TAG_NAME'] ?? '');
		if (!empty($fromField))
		{
			return $fromField;
		}

		return null;
	}

	private function fillTemplate(array $data): void
	{
		$grid = new Bitrix\Tasks\Grid\Tag\Grid($data, []);
		$this->arResult['CAN_USE_GRID_ACTIONS'] = $this->canUseGridActions;
		$this->arResult['GROUP_ID'] = $this->groupId;
		if ($this->groupId > 0)
		{
			$this->arResult['CAN_SEE_GROUP_TAGS'] = TagAccessController::can(
				$this->userId,
				ActionDictionary::ACTION_GROUP_TAG_READ,
				null,
				['GROUP_ID' => $this->groupId]
			);
			$this->arResult['GROUP_NAME'] = $this->getGroupName($this->groupId);
		}
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->arResult['FILTER_ID'] = self::FILTER_ID;
		$this->arResult['COLUMNS'] = $grid->prepareHeaders();
		$this->arResult['ROWS'] = $grid->prepareRows();
		$this->arResult['ACTION_PANEL'] = $grid->prepareGroupActions();
		$this->arResult['FILTER'] = $this->getFilterFields();
		$this->arResult['USER_ID'] = $this->userId;
		$this->arResult['IS_COLLAB'] = CollabRegistry::getInstance()->get($this->groupId) !== null;
	}

	public function getTasksByTagAction()
	{
		$request = $this->request->getPostList()->toArray();

		$isGridRequest = $this->request->get('grid_action') === 'more';
		$tagId = (int)$request['tagId'];
		$totalTasksCount = (int)$request['tasksCount'];
		$gridId = $request['gridId'] ?? '';
		$pathToTask = $request['pathToTask'] ?? '';
		$pathToUser = $request['pathToUser'] ?? '';
		$groupId = $request['groupId'] ?? '';

		$nav = new PageNavigation('page');
		$nav
			->allowAllRecords(true)
			->setPageSize(self::TASKS_PAGE_SIZE)
			->setCurrentPage($this->getCurrentPage())
			->initFromUri()
		;

		global $DB, $USER_FIELD_MANAGER;

		$taskProvider = new TaskProvider($DB, $USER_FIELD_MANAGER);

		$accessibleTasksCount = (int)$taskProvider->getCount([
			'CHECK_PERMISSIONS' => 'Y',
			'TAG_ID' => $tagId,
		])->Fetch()['CNT'];

		$isAllTasksNonAccessible = $totalTasksCount > 0 && $accessibleTasksCount === 0;
		if ($isAllTasksNonAccessible)
		{
			return new Component(
				'bitrix:tasks.error',
				'',
				[
					'TITLE' => Loc::getMessage('TASKS_TAG_TASKS_BY_TAG_IS_NON_ACCESSIBLE'),
				]
			);
		}

		//old provider should be reinitialized before each new query
		$taskProvider = new TaskProvider($DB, $USER_FIELD_MANAGER);
		$query = $taskProvider->getList(
			[],
			[
				'CHECK_PERMISSIONS' => 'Y',
				'TAG_ID' => $tagId,
			],
			[
				'ID',
				'TITLE',
				'RESPONSIBLE_ID',
				'RESPONSIBLE_NAME',
				'RESPONSIBLE_LAST_NAME',
				'REAL_STATUS',
			],
			[
				'NAV_PARAMS' => [
					'nPageSize' => self::TASKS_PAGE_SIZE,
					'iNumPage' => $nav->getCurrentPage(),
				],
			]
		);

		$res = [];
		while ($row = $query->Fetch())
		{
			$res[] = $row;
		}

		$this->arResult['NAV_OBJECT'] = $nav;

		$enableNextPage = (
			($nav->getCurrentPage() * $nav->getPageSize() + 1) <= $accessibleTasksCount
		);

		$rows = [];

		foreach ($res as $task)
		{
			$fullName = $task['RESPONSIBLE_NAME'] . " " . $task['RESPONSIBLE_LAST_NAME'];
			$rows[] = [
				'id' => (int)$task['ID'],
				'columns' => [
					'NAME' =>
						$this->prepareTaskGridRow((int)$task['ID'], $task['TITLE'], $pathToTask),
					'RESPONSIBLE_ID' =>
						$this->prepareTaskGridResponsible($task['RESPONSIBLE_ID'], $fullName, $pathToUser),
					'STATUS' =>
						Loc::getMessage('TASKS_TAG_GRID_TASK_ROW_CONTENT_STATUS_' . $task['REAL_STATUS']) ?? '',
				],
			];
		}

		$component = new Component(
			'bitrix:main.ui.grid',
			'',
			[
				'GRID_ID' => $gridId,
				'COLUMNS' => $this->prepareTaskGridColumns(),
				'ROWS' => $rows,

				'AJAX_MODE' => 'N',
				'AJAX_OPTION_JUMP' => 'N',
				'AJAX_OPTION_STYLE' => 'N',
				'AJAX_OPTION_HISTORY' => 'N',

				'SHOW_ACTION_PANEL' => false,
				'SHOW_TOTAL_COUNTER' => false,
				'SHOW_CHECK_ALL_CHECKBOXES' => false,
				'SHOW_ROW_CHECKBOXES' => false,
				'SHOW_SELECTED_COUNTER' => false,
				'SHOW_GRID_SETTINGS_MENU' => false,

				'ALLOW_COLUMNS_SORT' => true,
				'ALLOW_COLUMNS_RESIZE' => false,
				'ALLOW_INLINE_EDIT' => false,

				'SHOW_PAGINATION' => true,
				'NAV_STRING' => $this->getNavigationString($accessibleTasksCount, $totalTasksCount),
				'SHOW_MORE_BUTTON' => true,
				'NAV_PARAM_NAME' => 'page',
				'ENABLE_NEXT_PAGE' => $enableNextPage,
				'CURRENT_PAGE' => $nav->getCurrentPage(),
			]
		);

		if ($isGridRequest)
		{
			$response = new \Bitrix\Main\HttpResponse();
			$content = Json::decode($component->getContent());
			$response->setContent($content['data']['html']);

			return $response;
		}

		return $component;
	}

	private function prepareTaskGridColumns(): array
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_TAG_GRID_TASK_COLUMN_TASK_NAME'),
				'sort' => false,
				'first_order' => 'asc',
				'default' => true,
				'editable' => false,
				'type' => 'text',
			],
			'RESPONSIBLE_ID' => [
				'id' => 'RESPONSIBLE_ID',
				'name' => Loc::getMessage('TASKS_TAG_GRID_TASK_COLUMN_TASK_ASSIGNEE'),
				'sort' => false,
				'first_order' => 'asc',
				'default' => true,
				'editable' => false,
				'type' => 'text',
			],
			'STATUS' => [
				'id' => 'STATUS',
				'name' => Loc::getMessage('TASKS_TAG_GRID_TASK_COLUMN_TASK_STATUS'),
				'sort' => false,
				'first_order' => 'asc',
				'default' => true,
				'editable' => false,
				'type' => 'text',
			],
		];
	}

	private function prepareTaskGridRow(int $taskId, ?string $name, string $pathToTask): string
	{
		$pathToTask =
			str_replace(['#user_id#', '#action#', '#task_id#'], [$this->userId, 'view', $taskId], $pathToTask);

		if (!$name)
		{
			$name = '';
		}
		else
		{
			$name = htmlspecialcharsbx($name);
		}

		return "
			<a href=\"$pathToTask\" style=\"color: inherit\">
				$name
			</a>
		";
	}

	private function prepareTaskGridResponsible(int $userId, string $fullName, string $pathToUser): string
	{
		$pathToUser = str_replace(['#user_id#'], [$userId], $pathToUser);
		$fullName = htmlspecialcharsbx($fullName);

		return "
			<a href=\"$pathToUser\" style=\"color: inherit\">
				$fullName
			</a>
		";
	}

	private function getGroupName(int $groupId): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		if (empty($groupId))
		{
			return '';
		}

		$group = WorkgroupTable::getById($groupId)->fetchAll();

		if (empty($group))
		{
			return '';
		}

		return htmlspecialcharsbx($group[0]['NAME']);
	}

	private function canUseGridActions(int $groupId): bool
	{
		if (!$this->isGroupExists($groupId))
		{
			return false;
		}

		if (CurrentUser::get()->isAdmin())
		{
			return true;
		}

		$permissions = Group::getUserPermissionsInGroup($groupId, $this->userId);

		$codes = [
			'UserIsOwner',
			'UserIsScrumMaster',
			'UserCanModerateGroup',
		];
		foreach ($codes as $code)
		{
			if ($permissions[$code])
			{
				return true;
			}
		}

		return false;
	}

	private function isGroupExists(int $groupId): bool
	{
		if (WorkgroupTable::getById($groupId)->fetchObject())
		{
			return true;
		}

		return false;
	}

	private function getGroupMembers(int $groupId): array
	{
		if (!$this->isGroupExists($groupId))
		{
			return [];
		}
		$members = UserToGroupTable::getList([
			'select' => [
				'*',
			],
			'filter' => [
				'=GROUP_ID' => $groupId,
			],
		])->fetchAll();

		return array_map(function (array $el): int {
			return (int)$el['USER_ID'];
		}, $members);
	}

	public function executeComponent()
	{
		$this->fillTemplate($this->getRows());
		$this->includeComponentTemplate();
	}

	private function addNew(string $newTag, int $groupId): ?array
	{
		if ($groupId > 0)
		{
			if (
				!TagAccessController::can($this->userId, ActionDictionary::ACTION_TAG_CREATE, null, ['GROUP_ID' => $groupId])
			)
			{
				$this->addForbiddenError(); //???
				return [
					'success' => false,
					'error' => Loc::getMessage('TAG_ACTION_NOT_ALLOWED'),
				];
			}

			if ($this->tagService->isExistsByGroup($groupId, $newTag))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TASKS_TAG_ALREADY_EXISTS'),
				];
			}
			$this->tagService->addTagToGroup($newTag, $groupId);

			$members = $this->getGroupMembers($groupId);
			$members[] = $this->userId;
			$recipients = array_unique($members);

			PushService::addEvent($recipients, [
				'module_id' => 'tasks',
				'command' => PushCommand::TAG_ADDED,
				'params' => [
					'groupId' => $groupId,
				],
			]);

			return [
				'success' => true,
				'error' => '',
				'owner' => $this->getGroupName($groupId)
			];
		}

		if ($this->tagService->isExistsByUser($newTag))
		{
			return [
				'success' => false,
				'error' => Loc::getMessage('TASKS_TAG_ALREADY_EXISTS'),
			];
		}

		$this->tagService->addTagToUser($newTag);

		PushService::addEvent($this->userId, [
			'module_id' => 'tasks',
			'command' => PushCommand::TAG_ADDED,
			'params' => [
				'groupId' => $groupId,
			],
		]);

		return [
			'success' => true,
			'error' => '',
			'owner' => CurrentUser::get()->getFormattedName()
		];
	}

	private function getCurrentPage(): int
	{
		$page = $this->request->get('page');
		if (!is_null($page))
		{
			$pageNum = (int)$page;

			return ($pageNum < 0 ? 1 : $pageNum);
		}

		return 1;
	}

	private function getNavigationString(int $accessibleTasksCount, int $totalTasksCount): string
	{
		$nonAccessibleTasksCount = $totalTasksCount - $accessibleTasksCount;
		if ($nonAccessibleTasksCount === 0)
		{
			return Loc::getMessage(
				'TASKS_TAG_TASKS_BY_TAG_COUNT_TOTAL',
				[
					'#TASKS_COUNT#' => $totalTasksCount,
				]
			);
		}

		return Loc::getMessagePlural(
			'TASKS_TAG_TASKS_BY_TAG_COUNT',
			$nonAccessibleTasksCount,
			[
				'#ACCESSIBLE_COUNT#' => $accessibleTasksCount,
				'#NON_ACCESSIBLE_COUNT#' => $nonAccessibleTasksCount,
			]
		);
	}
}
