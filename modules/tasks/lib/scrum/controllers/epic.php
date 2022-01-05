<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Grid;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\UserService;
use Bitrix\Tasks\Util;

class Epic extends Controller
{
	/**
	 * @var CUserTypeManager
	 */
	private $userFieldManager;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		global $USER_FIELD_MANAGER;
		$this->userFieldManager = $USER_FIELD_MANAGER;

		$this->errorCollection = new ErrorCollection;
	}

	public function getDataForAddFormAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();



			return [];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function createEpicAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$epic = new EpicForm();

			$epic->setGroupId($post['groupId']);
			$epic->setName($post['name']);

			if ($epic->getName() === '')
			{
				$this->errorCollection->setError(
					new Error(
						Loc::getMessage('TASKS_SCRUM_EPIC_GRID_NAME_ERROR')
					)
				);

				return null;
			}

			$epic->setDescription($post['description']);
			$epic->setCreatedBy($post['createdBy'] ?? $userId);
			$epic->setColor($post['color']);

			$files = (is_array($post['files']) ? $post['files'] : []);

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
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function editEpicAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

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
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getListAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$gridId = (is_string($post['gridId'] ) ? $post['gridId'] : '');

			$isGridRequest = ($this->request->get('grid_id') != null);

			$nav = new PageNavigation('page');
			$nav->allowAllRecords(false)->setPageSize(10)->initFromUri();

			$epicService = new EpicService($userId);
			$userService = new UserService();

			$columns = $this->getUiGridColumns();

			$rows = [];
			$epicsList = [];

			$queryResult = $epicService->getList(
				[],
				['=GROUP_ID' => $groupId],
				$this->getGridOrder($gridId),
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

				$rows[] = [
					'id' => $epic->getId(),
					'columns' => [
						'NAME' => $this->getEpicGridColumnName($epic),
						'TAGS' => $this->getEpicGridColumnTags($userId, $epic),
						'TASKS_TOTAL' => $this->getEpicGridColumnTasksTotal($epic),
						'TASKS_COMPLETED' => $this->getEpicGridColumnTasksCompleted($epic),
						'USER' => $this->getEpicGridColumnUser($usersInfo),
					],
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
							'onclick' => 'BX.Tasks.Scrum.Epic.removeEpic('. $epicExtensionParams .');',
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
				'COLUMNS' => $columns,
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
		catch (\Exception $exception)
		{
			return '';
		}
	}

	public function getEpicAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

			$epicService = new EpicService();

			$epic = $epicService->getEpic($epicId);
			if (!$epic->getId())
			{
				$this->errorCollection->add([new Error('Epic not found')]);

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
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$epic->setDescription($outDescription);

			return $epic->toArray();
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function removeEpicAction()
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->setError(new Error('System error'));

			return null;
		}

		$post = $this->request->getPostList()->toArray();

		$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

		$userId = Util\User::getId();

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			$this->errorCollection->setError(new Error('System error'));

			return null;
		}

		$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

		$epicService = new EpicService();
		$pushService = (Loader::includeModule('pull') ? new PushService() : null);

		$epic = $epicService->getEpic($epicId);
		if (!$epic->getId())
		{
			$this->errorCollection->add([new Error('Epic not found')]);

			return null;
		}

		$epicService->removeEpic($epic, $pushService);

		return $epic->toArray();
	}

	public function getDescriptionEditorAction()
	{
		try
		{
			if (
				!Loader::includeModule('tasks')
				|| !Loader::includeModule('socialnetwork')
				|| !Loader::includeModule('disk')
			)
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$editorId = (is_string($post['editorId']) ? $post['editorId'] : '');
			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

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

	public function getEpicFilesAction()
	{
		try
		{
			if (
				!Loader::includeModule('tasks')
				|| !Loader::includeModule('socialnetwork')
				|| !Loader::includeModule('disk')
			)
			{
				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);
			$epicId = (is_numeric($post['epicId']) ? (int) $post['epicId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$epicService = new EpicService();

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
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage())
			);

			return null;
		}
	}

	public function getEpicInfoAction()
	{
		try
		{
			if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

			$post = $this->request->getPostList()->toArray();

			$groupId = (is_numeric($post['groupId']) ? (int) $post['groupId'] : 0);

			$userId = Util\User::getId();

			if (!$this->canReadGroupTasks($userId, $groupId))
			{
				$this->errorCollection->setError(new Error('System error'));

				return null;
			}

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
		catch (\Exception $exception)
		{
			return '';
		}
	}

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
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

	private function getEpicGridColumnTags(int $userId, EpicForm $epic): string
	{
		$itemService = new ItemService();
		$taskService = new TaskService($userId);

		$taskIds = $itemService->getTaskIdsByEpicId($epic->getId());
		$tags = $taskService->getTagsByTaskIds($taskIds);

		$tagsNodes = [];
		foreach ($tags as $tagName)
		{
			$tagsNodes[] = '<div>'.HtmlFilter::encode($tagName).'</div>';
		}

		return '<div class="tasks-scrum-epic-grid-tags">'.implode('', $tagsNodes).'</div>';
	}

	private function getEpicGridColumnTasksTotal(EpicForm $epic): string
	{
		$itemService = new ItemService();

		return '
			<div class="tasks-scrum-epic-grid-tasks-total">
				'.count($itemService->getTaskIdsByEpicId($epic->getId())).'
			</div>
		';
	}

	private function getEpicGridColumnTasksCompleted(EpicForm $epic): string
	{
		$itemService = new ItemService();

		$kanbanService = new KanbanService();
		$finishedTaskIds = $kanbanService->extractFinishedTaskIds($itemService->getTaskIdsByEpicId($epic->getId()));

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
	 * @return \CUserTypeManager|\UfMan
	 */
	private function getUserFieldManager()
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}