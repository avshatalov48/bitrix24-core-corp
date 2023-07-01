<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\Grid;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\RandomSequence;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;

class Epic extends Controller
{
	const ERROR_COULD_NOT_LOAD_MODULE = 'TASKS_EC_01';
	const ERROR_ACCESS_DENIED = 'TASKS_EC_02';

	/**
	 * @var CUserTypeManager
	 */
	private $userFieldManager;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		global $USER_FIELD_MANAGER;
		$this->userFieldManager = $USER_FIELD_MANAGER;
	}

	protected function processBeforeAction(Action $action)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return false;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
		$userId = User::getId();

		if (!Group::canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return false;
		}

		return parent::processBeforeAction($action);
	}

	/**
	 * Creates an epic.
	 *
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function createEpicAction(): ?array
	{
		$post = $this->request->getPostList()->toArray();

		$userId = Util\User::getId();

		$epic = new EpicForm();

		$epic->setGroupId($post['groupId'] ?? null);
		$epic->setName($post['name'] ?? null);

		if ($epic->getName() === '')
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SCRUM_EPIC_GRID_NAME_ERROR')
				)
			);

			return null;
		}

		$epic->setDescription($post['description'] ?? null);
		$epic->setCreatedBy($post['createdBy'] ?? $userId);

		$colorList = [
			'#aae9fc', '#bbecf1', '#98e1dc', '#e3f299', '#ffee95', '#ffdd93', '#dfd3b6', '#e3c6bb',
			'#ffad97', '#ffbdbb', '#ffcbd8', '#ffc4e4', '#c4baed', '#dbdde0', '#bfc5cd', '#a2a8b0'
		];
		$color = ($post['color'] ?? null) ? $post['color'] : array_rand(array_flip($colorList));
		$epic->setColor($color);

		$files = (is_array($post['files'] ?? null) ? $post['files'] : []);

		$epicService = new EpicService($userId);
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$epic = $epicService->createEpic($epic, $pushService);

		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		if ($files)
		{
			$epicService->attachFiles($this->userFieldManager, $epic->getId(), $files);

			if ($epicService->getErrors())
			{
				$this->errorCollection->add($epicService->getErrors());

				return null;
			}
		}

		return $epic->toArray();
	}

	/**
	 * Changes an epic.
	 *
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function editEpicAction(): ?array
	{
		$post = $this->request->getPostList()->toArray();

		$userId = Util\User::getId();

		$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);
		if (!$epicId)
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService = new EpicService($userId);
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		if (!Group::canReadGroupTasks($userId, $epic->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$inputEpic = new EpicForm();

		$inputEpic->setId($epicId);
		$inputEpic->setGroupId($post['groupId']);
		$inputEpic->setName($post['name']);
		if ($epic->getName() === '')
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_SCRUM_EPIC_GRID_NAME_ERROR')
				)
			);

			return null;
		}

		$inputEpic->setDescription($post['description']);
		$inputEpic->setCreatedBy($post['createdBy'] ?? $userId);
		$inputEpic->setModifiedBy($post['modifiedBy'] ?? $userId);
		$inputEpic->setColor($post['color']);

		$files = (is_array($post['files']) ? $post['files'] : []);

		$epicService->updateEpic($epic->getId(), $inputEpic, $pushService);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic not updated')]);

			return null;
		}

		$epicService->attachFiles($this->getUserFieldManager(), $epic->getId(), $files);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add([new Error('Epic files not attached')]);

			return null;
		}

		return $inputEpic->toArray();
	}

	/**
	 * Returns a component with a list of epics and processes requests for that component.
	 *
	 * @return Component|HttpResponse|string|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getListAction()
	{
		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		$userId = Util\User::getId();

		$gridId = (is_string($post['gridId'] ) ? $post['gridId'] : '');

		$isGridRequest = ($this->request->get('grid_id') != null);

		$nav = new PageNavigation('page');
		$nav->allowAllRecords(false)->setPageSize(10)->initFromUri();

		$epicService = new EpicService($userId);
		$userService = new UserService();

		$gridOptions = new Grid\Options($gridId);

		$gridVisibleColumns = $gridOptions->getVisibleColumns();
		if (empty($gridVisibleColumns))
		{
			$gridVisibleColumns = ['NAME', 'TAGS', 'TASKS_TOTAL', 'TASKS_COMPLETED', 'USER'];
		}

		$rows = [];
		$epicsList = [];

		$queryResult = $epicService->getList(
			[],
			['=GROUP_ID' => $groupId],
			$this->getGridOrder($gridOptions),
			$nav
		);
		if ($queryResult)
		{
			while ($data = $queryResult->fetch())
			{
				$epic = new EpicForm();

				$epic->fillFromDatabase($data);

				$epicsList[] = $epic;
			}
		}

		$randomGenerator = new RandomSequence(rand());

		$itemService = new ItemService();

		$n = 0;

		/** @var $epicsList EpicForm[] */
		foreach ($epicsList as $epic)
		{
			$n++;
			if ($n > $nav->getPageSize())
			{
				break;
			}

			$usersInfo = $userService->getInfoAboutUsers([$epic->getCreatedBy()]);

			$epicExtensionParams = '"'.$epic->getGroupId().'", "'.$epic->getId().'"';
			$epicExtensionRemoveParams = $epicExtensionParams . ', "'.$gridId.'"';

			$taskIds = $itemService->getTaskIdsByEpicId($epic->getId());

			$columns = [];
			if (in_array('NAME', $gridVisibleColumns))
			{
				$columns['NAME'] = $this->getEpicGridColumnName($epic);
			}
			if (in_array('TAGS', $gridVisibleColumns))
			{
				$columns['TAGS'] = $this->getEpicGridColumnTags($userId, $taskIds);
			}
			if (in_array('TASKS_TOTAL', $gridVisibleColumns))
			{
				$columns['TASKS_TOTAL'] = $this->getEpicGridColumnTasksTotal($epic, $taskIds);
			}
			if (in_array('TASKS_COMPLETED', $gridVisibleColumns))
			{
				$columns['TASKS_COMPLETED'] = $this->getEpicGridColumnTasksCompleted($epic, $taskIds);
			}
			if (in_array('USER', $gridVisibleColumns))
			{
				$columns['USER'] = $this->getUserColumn($usersInfo);
			}

			$rows[] = [
				'id' => $epic->getId() . $randomGenerator->randString(2),
				'columns' => $columns,
				'actions' => [
					[
						'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_VIEW'),
						'onclick' => 'BX.Tasks.Scrum.Epic.showView('. $epicExtensionParams .');',
					],
					[
						'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_EDIT'),
						'onclick' => 'BX.Tasks.Scrum.Epic.showEdit('. $epicExtensionParams .');',
					],
					[
						'text' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_ACTION_REMOVE'),
						'onclick' => 'BX.Tasks.Scrum.Epic.removeEpic('. $epicExtensionRemoveParams .');',
					]
				]
			];
		}

		$nav->setRecordCount($nav->getOffset() + $n);

		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		if (empty($rows))
		{
			return '';
		}

		$component = new Component('bitrix:main.ui.grid', '', [
			'GRID_ID' => $gridId,
			'COLUMNS' => $this->getUiGridColumns(),
			'ROWS' => $rows,
			'NAV_OBJECT' => $nav,
			'NAV_PARAMS' => ['SHOW_ALWAYS' => false],
			'SHOW_PAGINATION' => true,
			'SHOW_TOTAL_COUNTER' => false,
			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'ALLOW_COLUMNS_SORT' => true,
			'ALLOW_COLUMNS_RESIZE' => false,
			'ALLOW_INLINE_EDIT' => false,
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

	/**
	 * Returns a component with a list of tasks and processes requests for that component.
	 *
	 * @return Component|HttpResponse|string|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getTasksListAction()
	{
		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
		$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

		$userId = Util\User::getId();

		$gridId = (is_string($post['gridId'] ) ? $post['gridId'] : '');
		$completed = ($post['completed'] === 'Y');

		$isGridRequest = ($this->request->get('grid_id') != null);

		$nav = new PageNavigation('tasks-lists-navigation');
		$nav->setPageSize(10)->initFromUri();

		$gridOptions = new Grid\Options($gridId);

		$gridVisibleColumns = $gridOptions->getVisibleColumns();
		if (empty($gridVisibleColumns))
		{
			$gridVisibleColumns = ['NAME', 'STORY_POINTS', 'RESPONSIBLE'];
		}

		$epicService = new EpicService($userId);
		$taskService = new TaskService($userId);
		$itemService = new ItemService();
		$userService = new UserService();

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->add(
				[
					new Error(Loc::getMessage('TASKS_EC_ERROR_COULD_NOT_READ_EPIC'))
				]
			);

			return null;
		}

		$filter = [
			'GROUP_ID' => $groupId,
			'::SUBFILTER-EPIC' => ['EPIC' => $epic->getId()],
			'CHECK_PERMISSIONS' => 'Y',
			'ONLY_ROOT_TASKS' => 'N',
			'SCRUM_TASKS' => 'Y',
		];
		if ($completed)
		{
			$filter['=STATUS'] = \CTasks::STATE_COMPLETED;
		}

		$taskIds = $taskService->getTaskIdsByFilter($filter, $nav);

		(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($userId, $taskIds);

		$itemsStoryPoints = $itemService->getItemsStoryPointsBySourceId($taskIds);

		$randomGenerator = new RandomSequence(rand());

		$rows = [];

		foreach ($taskIds as $taskId)
		{
			$task = TaskRegistry::getInstance()->get($taskId);
			if (!$task)
			{
				continue;
			}

			$columns = [];
			if (in_array('NAME', $gridVisibleColumns))
			{
				$columns['NAME'] = $this->getTaskNameColumn($taskId, $task['TITLE']);
			}
			if (in_array('STORY_POINTS', $gridVisibleColumns))
			{
				$columns['STORY_POINTS'] = $itemsStoryPoints[$taskId] ?? '';
			}
			if (in_array('RESPONSIBLE', $gridVisibleColumns))
			{
				$usersInfo = $userService->getInfoAboutUsers([$task['RESPONSIBLE_ID']]);
				$columns['RESPONSIBLE'] = $this->getUserColumn($usersInfo);
			}

			$rows[] = [
				'id' => $taskId . $randomGenerator->randString(2),
				'columns' => $columns,
			];
		}

		if (empty($rows))
		{
			return '';
		}

		$component = new Component('bitrix:main.ui.grid', '', [
			'GRID_ID' => $gridId,
			'COLUMNS' => $this->getUiTasksGridColumns(),
			'ROWS' => $rows,
			'NAV_OBJECT' => $nav,
			'NAV_PARAMS' => ['SHOW_ALWAYS' => false],
			'SHOW_PAGINATION' => true,
			'SHOW_TOTAL_COUNTER' => false,
			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'ALLOW_COLUMNS_SORT' => true,
			'ALLOW_COLUMNS_RESIZE' => false,
			'ALLOW_INLINE_EDIT' => false,
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

	/**
	 * Returns an epic data.
	 *
	 * @param int $epicId Epic id.
	 * @return array|null
	 */
	public function getEpicAction(int $epicId): ?array
	{
		$userId = Util\User::getId();

		$epicService = new EpicService();

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('TASKS_EC_ERROR_COULD_NOT_READ_EPIC'))
			);

			return null;
		}

		if (!Group::canReadGroupTasks($userId, $epic->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$description = (new \CBXSanitizer)->sanitizeHtml($epic->getDescription());

		$userFields = $epicService->getFilesUserField($this->userFieldManager, $epicId);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		$taskService = new TaskService($userId);
		$outDescription = $taskService->convertDescription($description, $userFields);
		if ($taskService->getErrors())
		{
			$this->errorCollection->add($taskService->getErrors());

			return null;
		}

		$epic->setDescription($outDescription);

		return $epic->toArray();
	}

	/**
	 * Removes an epic.
	 *
	 * @param int $epicId Epic id.
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function removeEpicAction(int $epicId): ?array
	{
		$userId = Util\User::getId();

		$epicService = new EpicService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('TASKS_EC_ERROR_COULD_NOT_READ_EPIC'))
			);

			return null;
		}

		if (!Group::canReadGroupTasks($userId, $epic->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		if (!$epicService->removeEpic($epic, $pushService))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('TASKS_EC_ERROR_COULD_NOT_DELETE_EPIC'))
			);

			return null;
		}

		$epicService->deleteFiles($this->getUserFieldManager(), $epic->getId());

		return $epic->toArray();
	}

	/**
	 * Returns the component displaying the description editor.
	 *
	 * @param string $editorId Editor id.
	 * @param int $epicId Epic id.
	 * @return Component|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getDescriptionEditorAction(string $editorId, int $epicId = 0): ?Component
	{
		if (!Loader::includeModule('disk'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return null;
		}

		$epicService = new EpicService();

		$description = '';

		$epic = $epicService->getEpic($epicId);
		if ($epic->getId())
		{
			$description = (new \CBXSanitizer)->sanitizeHtml($epic->getDescription());
		}

		$buttons = ['UploadImage', 'UploadFile', 'CreateLink'];

		$epicService = new EpicService();

		$userFields = $epicService->getFilesUserField($this->userFieldManager, $epicId);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		$fileField = $userFields['UF_SCRUM_EPIC_FILES'];

		$params = [
			'FORM_ID' => $editorId,
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
				'id' => $editorId,
				'iframeCss' => 'body { padding-left: 10px !important; }',
				'fontSize' => '14px',
				'bInitByJS' => false,
				'height' => 100,
				'lazyLoad' => 'N',
				'bbCode' => true,
			]
		];

		return new Component('bitrix:main.post.form', '', $params);
	}

	/**
	 * Returns the component displaying the attached disk files.
	 *
	 * @param int $epicId Epic id.
	 * @return Component|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getEpicFilesAction(int $epicId): ?Component
	{
		if (!Loader::includeModule('disk'))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_INCLUDE_MODULE_ERROR'),
					self::ERROR_COULD_NOT_LOAD_MODULE
				)
			);

			return null;
		}

		$userId = Util\User::getId();

		$epicService = new EpicService();

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('TASKS_EC_ERROR_COULD_NOT_READ_EPIC'))
			);

			return null;
		}

		if (!Group::canReadGroupTasks($userId, $epic->getGroupId()))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage('TASKS_EC_ERROR_ACCESS_DENIED'),
					self::ERROR_ACCESS_DENIED
				)
			);

			return null;
		}

		$userFields = $epicService->getFilesUserField($this->userFieldManager, $epicId);
		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		$fileField = $userFields['UF_SCRUM_EPIC_FILES'];

		return new Component(
			'bitrix:system.field.view',
			$fileField['USER_TYPE']['USER_TYPE_ID'],
			[
				'arUserField' => $fileField,
			]
		);
	}

	/**
	 * Returns a data that the application might need.
	 *
	 * @param int $groupId Group id.
	 * @return bool[]|null
	 */
	public function getEpicInfoAction(int $groupId)
	{
		$userId = Util\User::getId();

		$existsEpic = false;

		$epicService = new EpicService($userId);

		$nav = new PageNavigation('epicsInfo');
		$nav->setPageSize(1);

		$queryResult = $epicService->getList(
			['ID'],
			['=GROUP_ID' => $groupId],
			['ID' => 'DESC'],
			$nav
		);
		if ($queryResult)
		{
			if ($queryResult->fetch())
			{
				$existsEpic = true;
			}
		}

		if ($epicService->getErrors())
		{
			$this->errorCollection->add($epicService->getErrors());

			return null;
		}

		return [
			'existsEpic' => $existsEpic
		];
	}

	private function getGridOrder(Grid\Options $gridOptions): array
	{
		$defaultSort = ['NAME' => 'DESC'];

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
				'default' => true,
			],
			[
				'id' => 'TASKS_TOTAL',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TASKS_TOTAL'),
				'default' => true,
			],
			[
				'id' => 'TASKS_COMPLETED',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_TASKS_COMPLETED'),
				'default' => true,
			],
			[
				'id' => 'USER',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_GRID_USER_SHORT'),
				'default' => true,
			]
		];
	}

	private function getUiTasksGridColumns(): array
	{
		return [
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_SCRUM_TASKS_GRID_NAME'),
				'default' => true,
			],
			[
				'id' => 'STORY_POINTS',
				'name' => Loc::getMessage('TASKS_SCRUM_TASKS_GRID_STORY_POINTS'),
				'default' => true,
			],
			[
				'id' => 'RESPONSIBLE',
				'name' => Loc::getMessage('TASKS_SCRUM_TASKS_GRID_RESPONSIBLE'),
				'default' => true,
			],
		];
	}

	private function getEpicGridColumnName(EpicForm $epic): string
	{
		$color = HtmlFilter::encode($epic->getColor());
		$name = HtmlFilter::encode($epic->getName());
		return '
			<div class="tasks-scrum-epic-grid-name">
				<div class="tasks-scrum-epic-grid-name-color" style="background-color: '.$color.';"></div>
				<a
					onclick="BX.Tasks.Scrum.Epic.showView(\''.$epic->getGroupId().'\', \''.$epic->getId().'\')"
					class="tasks-scrum-epic-name-label"
				>'.$name.'</a>
			</div>
		';
	}

	private function getEpicGridColumnTags(int $userId, array $taskIds): string
	{
		$taskService = new TaskService($userId);

		$tags = empty($taskIds) ? [] : $taskService->getTagsByTaskIds($taskIds);

		$tagsNodes = [];
		foreach ($tags as $tagName)
		{
			$tagsNodes[] = '<div>'.HtmlFilter::encode($tagName).'</div>';
		}

		return '<div class="tasks-scrum-epic-grid-tags">'.implode('', $tagsNodes).'</div>';
	}

	private function getEpicGridColumnTasksTotal(EpicForm $epic, array $taskIds): string
	{
		$count = count($taskIds);

		if ($count === 0)
		{
			return '<div class="tasks-scrum-epic-grid-empty-count">0</div>';
		}

		return '
			<div
				class="tasks-scrum-epic-grid-tasks-total"
				onclick="BX.Tasks.Scrum.Epic.showTasks(\''.$epic->getGroupId().'\', \''.$epic->getId().'\')"
			>'.$count.'</div>
		';
	}

	private function getEpicGridColumnTasksCompleted(EpicForm $epic, array $taskIds): string
	{
		$kanbanService = new KanbanService();

		$finishedTaskIds = $kanbanService->extractFinishedTaskIds($taskIds);

		$count = count($finishedTaskIds);

		if ($count === 0)
		{
			return '<div class="tasks-scrum-epic-grid-empty-count">0</div>';
		}

		return '
			<div
				class="tasks-scrum-epic-grid-tasks-completed"
				onclick="BX.Tasks.Scrum.Epic.showCompletedTasks(\''.$epic->getGroupId().'\', \''.$epic->getId().'\')"
			>
				'.$count.'
			</div>
		';
	}

	private function getUserColumn(array $usersInfo): string
	{
		return '
			<div>
				<a href="'.$usersInfo['pathToUser'].'">'.HtmlFilter::encode($usersInfo['name']).'</a>
			</div>
		';
	}

	private function getTaskNameColumn(int $taskId, string $name): string
	{
		return '
			<div
				class="tasks-scrum-epic-grid-tasks-completed"
				onclick="BX.Tasks.Scrum.Epic.showTask(\'' . $taskId . '\')"
			>
				' . HtmlFilter::encode($name) . '
			</div>
		';
	}

	/**
	 * @return \CUserTypeManager|\UfMan
	 */
	private function getUserFieldManager()
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}