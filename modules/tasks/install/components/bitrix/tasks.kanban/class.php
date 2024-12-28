<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

require_once __DIR__ . '/scrummanager.php';

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\Type\Collection;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Error;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

use Bitrix\Tasks\Component\Kanban\ScrumManager;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskTable;
use \Bitrix\Tasks\Kanban\StagesTable;
use \Bitrix\Tasks\Kanban\TaskStageTable;
use \Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Kanban\ProjectsTable;
use \Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Counter;
use \Bitrix\Tasks\Internals\Task;
use \Bitrix\Tasks\Internals\UserOption;
use \Bitrix\Tasks\Access\ActionDictionary;

use \Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Scrum\Form\EpicForm;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Service\EpicService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\PushService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Service\TaskService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Utility\ViewHelper;

use \Bitrix\Tasks\Components\Kanban\UserSettings;
use \Bitrix\Tasks\Components\Kanban\DisplayService;
use \Bitrix\Tasks\Components\Kanban\Services\Members;
use \Bitrix\Tasks\Components\Kanban\Services\CheckList;
use \Bitrix\Tasks\Components\Kanban\Services\Tags;
use \Bitrix\Tasks\Components\Kanban\Services\Files;
use \Bitrix\Tasks\Components\Kanban\Services\Logs;
use \Bitrix\Tasks\Components\Kanban\Services\Time;
use \Bitrix\Tasks\Components\Kanban\Services\Counters;

use Bitrix\Tasks\TourGuide;

class TasksKanbanComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	private const ORDER_OPTION = 'order_new_task_v2';
	const TASK_TYPE_USER = 'user';
	const TASK_TYPE_GROUP = 'group';

	const DEF_PAGE_SIZE = 20;

	const SESS_DATA_KEY = 'KANBAN_DATA';

	protected $filterInstance;

	protected $userId = 0;
	protected $timeOffset = 0;
	protected $taskType = '';
	protected $application = null;
	protected $select = array();
	protected $filter = array();
	protected $order = array();
	protected $listParams = array();
	protected $errors = array();
	protected $avatarSize = array('width' => 38, 'height' => 38);
	protected $previewSize = array('width' => 1000, 'height' => 1000);
	protected DisplayService $displayService;
	protected UserSettings $kanbanUserSettings;
	protected Time $timeService;
	protected Tags $tagsService;
	protected Logs $logsService;
	protected Files $filesService;
	protected Members $membersService;
	protected Counters $countersService;
	protected CheckList $checkListService;

	/**
	 * Init class' vars, check conditions.
	 * @return bool
	 */
	protected function init()
	{
		static $init = null;

		if ($init !== null)
		{
			return $init;
		}

		$init = true;
		$params =& $this->arParams;
		$result =& $this->arResult;
		$this->application = $GLOBALS['APPLICATION'];
		$this->timeOffset = \CTimeZone::GetOffset();

		Loc::loadMessages($this->getFile());

		// check fatal errors
		if ($init && !$this->application)
		{
			$this->addError('TASK_LIST_TASK_NOT_INSTALLED');
			$init = false;
		}
		if ($init && !Loader::includeModule('tasks'))
		{
			$this->addError('TASK_LIST_TASK_NOT_INSTALLED');
			$init = false;
		}
		if ($init && !\CBXFeatures::IsFeatureEnabled('Tasks'))
		{
			$this->addError('TASK_LIST_NOT_AVAILABLE_IN_THIS_EDITION');
			$init = false;
		}
		if ($init && ($this->userId = (int) \Bitrix\Tasks\Util\User::getId()) <= 0)
		{
			$this->addError('TASK_LIST_ACCESS_DENIED_V2');
			$init = false;
		}

		// init vars or exit on fatal
		if (!$init)
		{
			return $init;
		}

		$this->initVars();

		// get data of user or group
		if ($this->taskType === static::TASK_TYPE_USER)
		{
			$result['USER'] = \CUser::GetByID($params['USER_ID'])->fetch();
			if (!$result['USER'])
			{
				$this->addError('TASK_LIST_USER_NOT_FOUND');
				$init = false;
			}
		}
		elseif ($this->taskType === static::TASK_TYPE_GROUP)
		{
			$result['GROUP'] = \CSocNetGroup::GetByID($params['GROUP_ID']);
			if (!$result['GROUP'])
			{
				$this->addError('TASK_LIST_GROUP_NOT_FOUND');
				$init = false;
			}
			// check sonet perms
			elseif (!$this->canReadGroupTasks($params['GROUP_ID']))
			{
				$this->addError('TASK_LIST_ACCESS_TO_GROUP_DENIED_V2');
				$init = false;
			}
		}

		if (
			isset($this->arParams['GROUP_ID'])
			&& (int)$this->arParams['GROUP_ID'] > 0
			&& Loader::includeModule('socialnetwork')
		)
		{
			SocialNetwork::setLogDestinationLast(['SG' => [$this->arParams['GROUP_ID']]]);
		}

		$this->tagsService = new Tags();
		$this->timeService = new Time();
		$this->checkListService = new CheckList();
		$this->logsService = new Logs($this->userId);
		$this->kanbanUserSettings = new UserSettings(
			$this->getViewMode($this->arParams)
		);
		$this->filesService = new Files($this->previewSize);
		$this->membersService = new Members($this->arParams['~NAME_TEMPLATE']);
		$this->countersService = new Counters($this->userId, (int)$this->arParams['USER_ID']);
		$this->displayService = new DisplayService(
			$this->isScrum(),
			$this->kanbanUserSettings,
			$this->filesService,
			$this->tagsService,
			$this->membersService,
			$this->checkListService
		);

		return $init;
	}

	/**
	 * Set some vars.
	 */
	protected function initVars()
	{
		$params =& $this->arParams;

		// set from request
		$request = $this->request('params');
		$allowFromRequest = [
			'USER_ID', 'GROUP_ID', 'GROUP_ID_FORCED',
			'PERSONAL', 'TIMELINE_MODE', 'SPRINT_ID' , 'IS_COMPLETED_SPRINT'
		];
		foreach ($allowFromRequest as $code)
		{
			if (isset($request[$code]))
			{
				$params[$code] = $request[$code];
			}
		}

		// vars
		$params['SONET_LOAD'] = $sonetOn = Loader::includeModule('socialnetwork');
		$params['GROUP_ID_FORCED'] = $params['GROUP_ID_FORCED'] ?? false;
		$params['IS_AJAX'] = isset($params['IS_AJAX']) && $params['IS_AJAX'] === 'Y' ? 'Y' : 'N';
		$params['SET_TITLE'] = isset($params['SET_TITLE']) && $params['SET_TITLE'] === 'Y' ? 'Y' : 'N';
		$params['PERSONAL'] = isset($params['PERSONAL']) && $params['PERSONAL'] === 'Y' ? 'Y' : 'N';
		$params['TIMELINE_MODE'] = isset($params['TIMELINE_MODE']) && $params['TIMELINE_MODE'] === 'Y' ? 'Y' : 'N';
		$params['USER_ID'] = !isset($params['USER_ID']) || (int)$params['USER_ID'] <= 0 ? $this->userId : (int)$params['USER_ID'];
		$params['GROUP_ID'] = !isset($params['GROUP_ID']) || !$sonetOn ? 0 : max(0, (int)$params['GROUP_ID']);
		$params['SPRINT_ID'] = !isset($params['SPRINT_ID']) ? -1 : (int)$params['SPRINT_ID'];
		$params['IS_COMPLETED_SPRINT'] = ((isset($params['IS_COMPLETED_SPRINT']) && $params['IS_COMPLETED_SPRINT'] === 'Y') ? 'Y' : 'N');
		$params['~NAME_TEMPLATE'] = !isset($params['~NAME_TEMPLATE'])
									? \CSite::GetNameFormat(false)
									: str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), trim($params['~NAME_TEMPLATE']));

		$params['IS_SCRUM'] = false;

		$scrumManager = new ScrumManager($params['GROUP_ID']);
		if ($scrumManager->isScrumProject())
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			if ($params['SPRINT_ID'] >= 0)
			{
				$params['SPRINT_SELECTED'] = 'Y';
			}
			else
			{
				$params['SPRINT_ID'] = 0;
				$params['SPRINT_SELECTED'] = 'N';
			}

			$params['IS_SCRUM'] = true;

			$viewHelper = new ViewHelper($this->getSiteId());

			$params['IS_ACTIVE_SPRINT'] = ($viewHelper->getActiveView($params['GROUP_ID']) === 'active_sprint' ? 'Y' : 'N');
		}

		// force set last user group
		if ($params['PERSONAL'] === 'Y')
		{
			if ($params['GROUP_ID'] > 0)
			{
				$params['GROUP_ID_FORCED'] = true;
			}
			$this->arParams['STAGES_ENTITY_ID'] = $params['USER_ID'];
			if ($this->arParams['TIMELINE_MODE'] === 'Y')
			{
				StagesTable::setWorkMode(StagesTable::WORK_MODE_TIMELINE);
			}
			else
			{
				StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
			}
		}
		else if ($params['SPRINT_ID'] >= 0)
		{
			$this->arParams['STAGES_ENTITY_ID'] = $params['SPRINT_ID'];
		}
		else
		{
			if ($params['GROUP_ID'] == 0 && $sonetOn)
			{
				$params['GROUP_ID'] = $this->getLastGroupId();
				$params['GROUP_ID_FORCED'] = true;
			}
			else
			{
				if (isset($params['GROUP_ID_FORCED']))
				{
					$params['GROUP_ID_FORCED'] = (boolean)$params['GROUP_ID_FORCED'];
				}
			}
			$this->arParams['STAGES_ENTITY_ID'] = $params['GROUP_ID'];
			StagesTable::setWorkMode(StagesTable::WORK_MODE_GROUP);
		}

		// remeber view
//		Filter\Task::getListStateInstance()->setViewMode(
//			$params['PERSONAL'] == 'Y'
//			? \CTaskListState::VIEW_MODE_PLAN
//			: \CTaskListState::VIEW_MODE_KANBAN
//		);

		// preview sizes
		if (isset($params['PREVIEW_WIDTH']) && $params['PREVIEW_WIDTH'] > 0)
		{
			$this->previewSize['width'] = $params['PREVIEW_WIDTH'];
		}
		if (isset($params['PREVIEW_HEIGHT']) && $params['PREVIEW_HEIGHT'] > 0)
		{
			$this->previewSize['height'] = $params['PREVIEW_HEIGHT'];
		}

		// sonet group
		if ($params['GROUP_ID'] > 0)
		{
			$this->taskType = static::TASK_TYPE_GROUP;
		}
		else
		{
			$this->taskType = static::TASK_TYPE_USER;
		}

		//		if (
		//			$this->taskType == static::TASK_TYPE_GROUP /*&&
		//			!$params['GROUP_ID_FORCED']*/
		//		)
		//		{
		//			Filter\Task::setGroupId($params['GROUP_ID']);
		//		}
		//		else
		//		{
		//			Filter\Task::setUserId($params['USER_ID']);
		//		}

		if (StagesTable::getWorkMode() === StagesTable::WORK_MODE_ACTIVE_SPRINT)
		{
			$taskService = new TaskService($params['USER_ID'], $this->application);
			$this->filterInstance = $taskService->getFilterInstance(
				$this->arParams['GROUP_ID'],
				$this->arParams['IS_COMPLETED_SPRINT'] === 'Y' ? 'complete' : 'active'
			);
		}
		else
		{
			$this->filterInstance = Filter::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
		}

		$this->arParams['DEFAULT_ROLEID'] = $this->filterInstance->getDefaultRoleId();
		$this->arResult['DEFAULT_PRESET_KEY'] = $this->filterInstance->getDefaultPresetKey();


		// navigation
		if (!isset($params['ITEMS_COUNT']))
		{
			$params['ITEMS_COUNT'] = static::DEF_PAGE_SIZE;
		}
		// temporary we set DEF_PAGE_SIZE always
		$params['ITEMS_COUNT'] = static::DEF_PAGE_SIZE;
		if (!isset($params['ITEMS_PAGE']))
		{
			$params['ITEMS_PAGE'] = 1;
		}

		// external filter
		if (isset($params['FILTER']) && is_array($params['FILTER']))
		{
			$this->filter = $params['FILTER'];
		}

		// pathes
		$params['~PATH_TO_USER_PROFILE'] = !isset($params['~PATH_TO_USER_PROFILE']) || trim($params['~PATH_TO_USER_PROFILE']) == ''
									? $this->getPage() . '?page=user_profile&user_id=#user_id#'
									: trim($params['~PATH_TO_USER_PROFILE']);
		$params['~PATH_TO_USER_TASKS'] = !isset($params['~PATH_TO_USER_TASKS']) || trim($params['~PATH_TO_USER_TASKS']) == ''
									? Option::get('tasks', 'paths_task_user', null, SITE_ID)
									: trim($params['~PATH_TO_USER_TASKS']);
		$params['~PATH_TO_USER_TASKS_TASK'] = !isset($params['~PATH_TO_USER_TASKS_TASK']) || trim($params['~PATH_TO_USER_TASKS_TASK']) == ''
									? Option::get('tasks', 'paths_task_user_action', null, SITE_ID)
									: trim($params['~PATH_TO_USER_TASKS_TASK']);
		$params['~PATH_TO_USER_TASKS_TEMPLATES'] = !isset($params['~PATH_TO_USER_TASKS_TEMPLATES']) || trim($params['~PATH_TO_USER_TASKS_TEMPLATES']) == ''
									? $this->getPage() . '?page=user_templates&user_id=#user_id#'
									: trim($params['~PATH_TO_USER_TASKS_TEMPLATES']);
		$params['~PATH_TO_GROUP_TASKS'] = !isset($params['~PATH_TO_GROUP_TASKS']) || trim($params['~PATH_TO_GROUP_TASKS']) == ''
									? Option::get('tasks', 'paths_task_group', null, SITE_ID)
									: trim($params['~PATH_TO_GROUP_TASKS']);
		$params['~PATH_TO_GROUP_TASKS_TASK'] = !isset($params['~PATH_TO_GROUP_TASKS_TASK']) || trim($params['~PATH_TO_GROUP_TASKS_TASK']) == ''
									? Option::get('tasks', 'paths_task_group_action', null, SITE_ID)
									: trim($params['~PATH_TO_GROUP_TASKS_TASK']);

		// replace for id
		if ($this->taskType == static::TASK_TYPE_USER || $params['GROUP_ID_FORCED'])
		{
			$params['~PATH_TO_TASKS'] = str_replace('#user_id#', $params['USER_ID'], $params['~PATH_TO_USER_TASKS']);
			$params['~PATH_TO_TASKS_TASK'] = str_replace('#user_id#', $params['USER_ID'], $params['~PATH_TO_USER_TASKS_TASK']);
		}
		elseif ($this->taskType == static::TASK_TYPE_GROUP)
		{
			$params['~PATH_TO_TASKS'] = str_replace('#group_id#', $params['GROUP_ID'], $params['~PATH_TO_GROUP_TASKS']);
			$params['~PATH_TO_TASKS_TASK'] = str_replace('#group_id#', $params['GROUP_ID'], $params['~PATH_TO_GROUP_TASKS_TASK']);
		}
		$params['~PATH_TO_TEMPLATES'] = str_replace('#user_id#', $this->userId, $params['~PATH_TO_USER_TASKS_TEMPLATES']);

		$this->arResult['CALENDAR_SETTINGS'] = [];
		$deadlineTimeSettings = \CUserOptions::getOption('tasks.bx.calendar.deadline', 'time_visibility', []);
		$this->arResult['CALENDAR_SETTINGS']['deadlineTimeVisibility'] = (
			(isset($deadlineTimeSettings['visibility']) && $deadlineTimeSettings['visibility'] == 'Y') ? 'Y' : 'N'
		);

		$this->arResult['IS_TOOL_AVAILABLE'] = $this->isToolAvailable();
	}

	private function isToolAvailable(): bool
	{
		return (new Settings())->isToolAvailable(Settings::TOOLS['base_tasks']);
	}

	/**
	 * Returns all members of tasks.
	 * @param array $taskData Task data (single item from $this->getData).
	 * @return array
	 */
	protected function getMembersTask(array $taskData): array
	{
		$members = [];

		// author and responsible
		$members[] = $taskData['data']['author']['id'] ?? null;
		$members[] = $taskData['data']['responsible']['id'] ?? null;

		// other task members
		$res = \CTaskMembers::getList(
			[],
			['TASK_ID' => $taskData['id']]
		);
		while ($row = $res->fetch())
		{
			$members[] = $row['USER_ID'];
		}

		// group members
		if ($this->arParams['GROUP_ID'])
		{
			$groupMembers = SocialNetwork\User::getUsersCanPerformOperation(
				$this->arParams['GROUP_ID'],
				'view_all'
			);
			$members = array_unique(array_merge($members, $groupMembers));
		}

		return $members;
	}

	/**
	 * Check access to group tasks for current user.
	 * @param int $groupId Id of group.
	 * @return boolean
	 */
	protected function canReadGroupTasks($groupId)
	{
		return \Bitrix\Tasks\Integration\SocialNetwork\Group::canReadGroupTasks($this->userId, $groupId);
	}

	/**
	 * Check access to sort tasks for current user.
	 * @return boolean
	 */
	protected function canSortTasks()
	{
		static $access = null;

		if ($access !== null)
		{
			return $access;
		}

		$groupId = $this->arParams['GROUP_ID'];

		if ($this->arParams['PERSONAL'] === 'Y')
		{
			$access = $this->isAdmin();
		}
		elseif ($groupId > 0)
		{
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'sort');
			$access = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		}
		else
		{
			$access = $this->isAdmin();
		}

		return $access;
	}

	/**
	 * Check access to create tasks for current user.
	 * @return boolean
	 */
	protected function canCreateTasks()
	{
		if (!\Bitrix\Tasks\Util\Restriction::canManageTask())
		{
			return false;
		}
		if ($this->taskType == static::TASK_TYPE_GROUP)
		{
			$groupId = $this->arParams['GROUP_ID'];
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'create_tasks');
			return is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		}
		return true;
	}

	/**
	 * Get admins and group moderators.
	 * @return array
	 */
	protected function getAdmins()
	{
		$users = array();
		// site admins
		$res = \Bitrix\Main\UserGroupTable::getList(array(
			'filter' => array(
				'GROUP_ID' => 1
			)
		));
		while ($row = $res->fetch())
		{
			$users[] = $row['USER_ID'];
		}
		// group admins
		if ($this->arParams['SONET_LOAD'])
		{
			$res = \CSocNetUserToGroup::GetList(
				array(),
				array(
					'GROUP_ID' => $this->arParams['GROUP_ID'],
					'ROLE' => array(
						SONET_ROLES_OWNER,
						SONET_ROLES_MODERATOR
					),
					'USER_ACTIVE' => 'Y',
					'GROUP_ACTIVE' => 'Y'
				),
				false, false,
				array(
					'USER_ID'
				)
			);
			while ($row = $res->fetch())
			{
				$users[] = $row['USER_ID'];
			}
		}

		if (!empty($users))
		{
			$res = \CUser::GetList(
					'timestamp_x',
					'desc',
					array(
						'ID' => implode(' | ', $users),
						'ACTIVE' => 'Y',
						'!ID' => $this->userId
					),
					[
						'FIELDS' => [
							'ID',
							'PERSONAL_PHOTO',
							'LOGIN', 'NAME', 'LAST_NAME',
							'SECOND_NAME', 'TITLE', 'EMAIL'
						]
					]
			);
			$users = array();
			while ($row = $res->fetch())
			{
				if ($row['PERSONAL_PHOTO'])
				{
					$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
						$row['PERSONAL_PHOTO'],
						$this->avatarSize,
						BX_RESIZE_IMAGE_EXACT
					);
					if ($row['PERSONAL_PHOTO'])
					{
						$row['PERSONAL_PHOTO'] = $row['PERSONAL_PHOTO']['src'];
					}
				}
				$users[$row['ID']] = array(
					'id' => $row['ID'],
					'name' => \CUser::FormatName(
						$this->arParams['~NAME_TEMPLATE'],
						$row, true, false
					),
					'img' => $row['PERSONAL_PHOTO']
				);
			}
		}

		return $users;
	}

	/**
	 * Get last by activity group id.
	 * @return int
	 */
	protected function getLastGroupId()
	{
		$lastGroupId = 0;

		if (!$this->arParams['SONET_LOAD'])
		{
			return $lastGroupId;
		}

		return \Bitrix\Tasks\Integration\SocialNetwork\Group::getLastViewedProject($this->userId);
	}

	/**
	 * Is debug hit?
	 * @return boolean
	 */
	protected function isDebug()
	{
		return $this->request('debug') == 'Y';
	}

	/**
	 * Get request-var for http-hit.
	 * @param string $var Request-var code.
	 * @return mixed
	 */
	protected function request($var)
	{
		static $request = null;

		if ($request === null)
		{
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
		}

		return $request->get($var);
	}

	/**
	 * Reload current page.
	 * @param array $deleteParams Params to delete from uri.
	 * @return void
	 */
	protected function reloadPage(array $deleteParams = array())
	{
		$context = Application::getInstance()->getContext();
		$uri = new \Bitrix\Main\Web\Uri($context->getRequest()->getRequestUri());
		\LocalRedirect($uri->deleteParams($deleteParams)->getUrl(), true);
	}

	/**
	 * Get current page.
	 * @return string
	 */
	protected function getPage()
	{
		static $page = null;
		if ($page === null)
		{
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			$page = $uri->getPath();
		}
		return $page;
	}

	/**
	 * Get current URI.
	 * @return string
	 */
	protected function getURI()
	{
		static $uri = null;
		if ($uri === null)
		{
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
		}
		return $uri;
	}

	/**
	 * Get __FILE__.
	 * @return string
	 */
	protected function getFile()
	{
		return __FILE__;
	}

	/**
	 * Get current errors.
	 * @param bool $string Convert Errors to string.
	 * @return array
	 */
	protected function getErrorsOld($string = true)
	{
		if ($string)
		{
			$errors = array();
			foreach ($this->errors as $error)
			{
				$errors[] = $error->getMessage();
			}
			return $errors;
		}

		return $this->errors;
	}

	/**
	 * Add one more error.
	 * @param string $code Code of error (lang code).
	 * @return void
	 */
	protected function addError($code)
	{
		$message = Loc::getMessage($code);
		$this->errors[] = new Error(!empty($message) ? $message : $code, $code);
	}

	/**
	 * Copy error from other instance.
	 * @param array $errors Array of Error.
	 * @return void
	 */
	protected function copyError(array $errors)
	{
		foreach ($errors as $error)
		{
			$this->errors[] = new Error(
				$error->getMessage(),
				$error->getCode()
			);
		}
	}

	/**
	 * I am the owner of this list?
	 * @return bool
	 */
	protected function itsMyTasks()
	{
		return (
					$this->taskType == static::TASK_TYPE_USER &&
					$this->userId == $this->arParams['USER_ID']
				)
				||
				(
					$this->userId == $this->arParams['USER_ID'] &&
					$this->arParams['GROUP_ID_FORCED']
				);
	}

	/**
	 * Current user is admin for this Kanban?
	 * @return bool
	 */
	protected function isAdmin()
	{
		if ($this->arParams['PERSONAL'] == 'Y')
		{
			return $this->itsMyTasks();
		}

		if ($this->taskType == static::TASK_TYPE_GROUP)
		{
			$right = \CSocNetUserToGroup::GetUserRole($this->userId, $this->arParams['GROUP_ID']);
			if ($right == SONET_ROLES_OWNER || $right == SONET_ROLES_MODERATOR)
			{
				return true;
			}
		}

		return $GLOBALS['USER']->isAdmin() || \CTasksTools::IsPortalB24Admin();
	}

	/**
	 * Get select array.
	 * @return array
	 */
	protected function getSelect()
	{
		// by default
		$this->select[] = 'ID';
		$this->select = array_merge(
			$this->select,
			[
				'TITLE',
				'REAL_STATUS',
				'PRIORITY',
				'DEADLINE',
				'DATE_START',
				'END_DATE_PLAN',
				'CREATED_DATE',
				'CREATED_BY',
				'RESPONSIBLE_ID',
				'AUDITORS',
				'ACCOMPLICES',
				'ALLOW_CHANGE_DEADLINE',
				'ALLOW_TIME_TRACKING',
				'TIME_SPENT_IN_LOGS',
				'TIME_ESTIMATE',
				'STAGE_ID',
				'IS_MUTED',
				'GROUP_ID',
				'FLOW_ID',
				'PARENT_ID',
				'MARK',
				'UF_CRM_TASK',
			]
		);

		$this->select = array_unique($this->select);

		return $this->select;
	}

	/**
	 * Add one item to the filter.
	 * @param string $key Key for select.
	 * @return void
	 */
	protected function addSelect($key)
	{
		$this->filter[] = $key;
	}

	/**
	 * Get filter array.
	 * @return array
	 */
	protected function getFilter(): array
	{
		static $filling = false;

		if ($filling)
		{
			return $this->filter;
		}

		$filling = true;

		$params =& $this->arParams;
		$filter =& $this->filter;

		$uiFilter = $this->filterInstance->process();
		if (array_key_exists('GROUP_ID', $this->arParams) && (int)$this->arParams["GROUP_ID"] > 0)
		{
			$uiFilter['GROUP_ID'] = $this->arParams["GROUP_ID"];
			unset($uiFilter['MEMBER']);
		}

		$filter = array_merge($filter, $uiFilter);

		// by default
		if (!array_key_exists('CHECK_PERMISSIONS', $filter))
		{
			$filter['CHECK_PERMISSIONS'] = 'Y';
		}
		if ($params['PERSONAL'] !== 'Y' || $params['GROUP_ID'] > 0)
		{
			$filter['GROUP_ID'] = $params['GROUP_ID'];
		}
		$filter['ONLY_ROOT_TASKS'] = 'N';

		return $filter;
	}

	/**
	 * Add one item to the filter.
	 * @param string $key Key for filter.
	 * @param string $value Value for filter.
	 * @return void
	 */
	protected function addFilter($key, $value)
	{
		$this->filter[$key] = $value;
	}

	/**
	 * Remove one item to the filter.
	 * @param string $key Key for filter.
	 * @return void
	 */
	protected function deleteFilter(string $key): void
	{
		if (isset($this->filter[$key]))
		{
			unset($this->filter[$key]);
		}
	}

	/**
	 * Set page id / page number.
	 * @param int $id Page id.
	 * @return void
	 */
	protected function setPageId($id)
	{
		$this->arParams['ITEMS_PAGE'] = $id;
	}

	/**
	 * Returns order array.
	 * @return array
	 */
	protected function getOrder()
	{
		if ($this->getNewTaskOrder() == 'actual')
		{
			$this->order = [
				'ACTIVITY_DATE' => 'desc',
				'ID' => 'asc'
			];
		}
		else
		{
			$this->order = [
				'SORTING_ORDER' => 'ASC',
				'STATUS_COMPLETE' => 'ASC',
				'DEADLINE' => 'ASC,NULLS',
				'ID' => 'ASC'
			];
		}

		return $this->order;
	}

	/**
	 * Get nav and other list params.
	 * @return array
	 */
	protected function getListParams()
	{
		$this->listParams['NAV_PARAMS'] = array(
			'nPageSize' => $this->arParams['ITEMS_COUNT'],
			'iNumPage' => $this->arParams['ITEMS_PAGE'],
			'bDescPageNumbering' => false,
			'NavShowAll' => false,
			'bShowAll' => false
		);
		// personl sort
		if (
			$this->arParams['PERSONAL'] !== 'Y' &&
			$this->taskType == static::TASK_TYPE_GROUP
		)
		{
			$this->listParams['SORTING_GROUP_ID'] = $this->arParams['GROUP_ID'];
		}
		return $this->listParams;
	}

	/**
	 * Make query like ORM.
	 * @param array $params Params with order, filter, nav, select keys.
	 * @param bolean $asIs Return result as is.
	 * @return array
	 */
	protected function getList(array $params, $asIs = false)
	{
		// if personal, we look at another field
		if (
			(
				$this->arParams['PERSONAL'] === 'Y' ||
				$this->arParams['SPRINT_ID'] > 0
			)
			&&
			isset($params['filter']['STAGE_ID'])
		)
		{
			$params['filter']['STAGES_ID'] = $params['filter']['STAGE_ID'];
			unset($params['filter']['STAGE_ID']);
		}

		if (array_key_exists('USER_ID', $this->arParams))
		{
			$params['navigate']['TARGET_USER_ID'] = $this->arParams['USER_ID'];
		}


		[$rows, $res] = CTaskItem::fetchList(
			$this->userId,
			($params['order'] ?? []),
			($params['filter'] ?? []),
			($params['navigate'] ?? []),
			($params['select'] ?? [])
		);

		$taskIds = [];
		foreach ($rows as $row)
		{
			$id = $row->getId();
			$taskIds[$id] = $id;
		}

		(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload($this->userId, $taskIds);

		return ($asIs ? [$rows, $res] : $rows);
	}

	/**
	 * Fill data-array with files count and background.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getFiles(array $items)
	{
		return $this->filesService->getFiles($items);
	}

	/**
	 * Fill data-array with checklist counts.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getCheckList(array $items)
	{
		return $this->checkListService->getCheckList($items);
	}

	/**
	 * Fill data-array with tags.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getTags(array $items)
	{
		return $this->tagsService->getTags($items);
	}

	/**
	 * Fill data-array with time starting delta.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getTimeStarted(array $items)
	{
		return $this->timeService->getTimeStarted($items);
	}

	/**
	 * Fill data-array with new log-data.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getNewLog(array $items)
	{
		return $this->logsService->getNewLog($items);
	}

	/**
	 * Get info about users.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getUsers(array $items)
	{
		return $this->membersService->getUsers($items);
	}

	/**
	 * @param array $taskData
	 * @return array
	 */
	protected function getDeadlineProps(array $taskData): array
	{
		$deadline = new Bitrix\Tasks\Grid\Task\Row\Content\Date\Deadline($taskData);

		$rawValue = $deadline->formatDate($taskData['DEADLINE']);
		$value = $rawValue;
		$color = '';
		$fill = false;

		$state = $deadline->getDeadlineStateData();
		if ($state['state'])
		{
			$value = $state['state'];
			$color = "ui-label-{$state['color']}";
			$fill = ($state['fill'] ? true : false);
		}

		return [
			'rawValue' => $rawValue,
			'value' => $value,
			'color' => $color,
			'fill' => $fill,
		];
	}

	/**
	 * @param int $userId
	 * @param array $taskData
	 * @return bool
	 */
	protected function isMember(int $userId, array $taskData): bool
	{
		$members = array_unique(
			array_merge(
				[$taskData['CREATED_BY'], $taskData['RESPONSIBLE_ID']],
				$taskData['ACCOMPLICES'],
				$taskData['AUDITORS']
			)
		);
		$members = array_map('intval', $members);

		return in_array($userId, $members, true);
	}

	/**
	 * @param string $date
	 * @return int
	 */
	protected function getDateTimestamp($date): int
	{
		$timestamp = MakeTimeStamp($date);

		if ($timestamp === false && isset($date))
		{
			$timestamp = strtotime($date);
			if ($timestamp !== false)
			{
				$timestamp += CTimeZone::GetOffset()
					- \Bitrix\Tasks\Util\Type\DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	protected function isExpired(int $timestamp): bool
	{
		return $timestamp && ($timestamp <= $this->getNow());
	}

	/**
	 * @return int
	 */
	protected function getNow(): int
	{
		return (new \Bitrix\Tasks\Util\Type\DateTime())->getTimestamp() + CTimeZone::GetOffset($this->userId);
	}

	/**
	 * Send handler event.
	 * @param string $codeEvent Code of event.
	 * @param mixed $data Data for event.
	 * @return array
	 */
	protected function sendEvent($codeEvent, $data)
	{
		$event = new \Bitrix\Main\Event('tasks', $codeEvent, array(
			'DATA' => $data,
		));
		$event->send();
		foreach ($event->getResults() as $result)
		{
			if ($result->getResultType() != \Bitrix\Main\EventResult::ERROR)
			{
				if (($modified = $result->getModified()))
				{
					if (isset($modified['DATA']))
					{
						$data = $modified['DATA'];
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Get demo columns.
	 * @param int $viewId View id.
	 * @return array
	 */
	protected function getDemoColumns($viewId)
	{
		$columns = array();

		foreach (StagesTable::getStages($viewId) as $stage)
		{
			$columns[] = array(
				'id' => -1 * $stage['ID'],
				'name' => $stage['TITLE'],
				'color' => $stage['COLOR'],
				'type' => $stage['SYSTEM_TYPE'],
				'sort' => $stage['SORT'],
				'total' => 0,
				'canSort' => true
			);
		}

		return $columns;
	}

	/**
	 * Base method for getting columns.
	 * @param bool $assoc Return as associative.
	 * @return array
	 */
	protected function getColumns(bool $assoc = false): array
	{
		$columns = [];
		$counts = [];
		$filteredStages = [];
		$timeLineMode = $this->arParams['TIMELINE_MODE'] === 'Y';
		$viewModeForAnalytics = Analytics::getInstance($this->arParams['USER_ID'], $this->arParams['GROUP_ID'] ?? 0)->getViewStateName();
		$filter = $this->getFilter();
		$stageList = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);

		// get counts
		if (!$timeLineMode)
		{
			$counts = StagesTable::getStagesCount(
				$stageList,
				$filter,
				$this->arParams['USER_ID'] ?: false
			);
		}

		if (
			StagesTable::getWorkMode() === StagesTable::WORK_MODE_GROUP
			|| StagesTable::getWorkMode() === StagesTable::WORK_MODE_ACTIVE_SPRINT
		)
		{
			if (!empty($filter['::SUBFILTER-STAGE_ID']) && is_array($filter['::SUBFILTER-STAGE_ID']['STAGE_ID']))
			{
				foreach ($filter['::SUBFILTER-STAGE_ID']['STAGE_ID'] as $filteredStage)
				{
					if (array_key_exists($filteredStage, $stageList))
					{
						$filteredStages[] = $filteredStage;
					}
				}

				$this->filter['::SUBFILTER-STAGE_ID']['STAGE_ID'] = $filteredStages;
			}
		}

		// get columns
		foreach ($stageList as $stage)
		{
			$count = 0;

			if (!empty($filteredStages) && !in_array($stage['ID'], $filteredStages))
			{
				continue;
			}

			if ($stage['ADDITIONAL_FILTER'])
			{
				$filterTmp = array_merge(
					$filter,
					$stage['ADDITIONAL_FILTER']
				);
				$count = CTasks::GetCountInt($filterTmp);
			}
			else
			{
				$stages = (array)StagesTable::getStageIdByCode(
					$stage['ID'],
					$this->arParams['STAGES_ENTITY_ID']
				);
				foreach ($stages as $stId)
				{
					if (isset($counts[$stId]))
					{
						$count += $counts[$stId];
					}
				}
			}

			$canAddItem = (isset($this->arParams['SPRINT_SELECTED']) && $this->arParams['SPRINT_SELECTED'] === 'Y')
				? $stage['TO_UPDATE_ACCESS'] !== false
				&& empty($this->arResult['MANDATORY_EXISTS'])
				&& (isset($this->arParams['IS_ACTIVE_SPRINT']) && $this->arParams['IS_ACTIVE_SPRINT'] === 'Y')
				: $stage['TO_UPDATE_ACCESS'] !== false
			;

			$columns[$stage['ID']] = [
				'id' => $stage['ID'],
				'name' => $stage['TITLE'],
				'color' => $stage['COLOR'],
				'type' => $stage['SYSTEM_TYPE'],
				'sort' => $stage['SORT'],
				'total' => $count,
				'canSort' => $this->isAdmin() && $this->arParams['TIMELINE_MODE'] !== 'Y',
				'canAddItem' => $canAddItem,
				'viewStateName'=> $viewModeForAnalytics,
			];
		}

		$columns = $this->sendEvent('KanbanComponentGetColumns', $columns);

		return $assoc ? $columns : array_values($columns);
	}

	/**
	 * Fill one item with task data.
	 * @param object $task Task item.
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function fillData(CTaskItem $task)
	{
		static $endDayTime = null;
		static $date = null;

		if ($date === null)
		{
			$date = new DateTime;
		}

		if ($endDayTime === null)
		{
			$endDayTime = Option::get('calendar', 'work_time_end', 19);
		}

		try
		{
			$item = $task->getData();
		}
		catch (TasksException $e)
		{
			return null;
		}

		$activityDate = $item['ACTIVITY_DATE'] ?? null;

		$canEdit = $task->isActionAllowed(CTaskItem::ACTION_EDIT);
		$markProp = (!in_array($item['MARK'], ['P', 'N'])) ? 'NONE': $item['MARK'];
		$data = array(
			// base
			'id' => $item['ID'],
			'parentId' => $item['PARENT_ID'],
			'stage_id' => $item['STAGE_ID'],
			'groupId' => $item['GROUP_ID'],
			'name' => $this->displayService->fillTitle($item['TITLE']),
			'background' => '',
			'author' => $item['CREATED_BY'],
			'responsible' => $item['RESPONSIBLE_ID'],
			'tags' => [],
			'counter' => 0,
			'deadline' => $this->getDeadlineProps($item),
			'deadline_visibility' => $this->displayService->fillDeadLineVisibility(),
			// time
			'time_tracking' => $item['ALLOW_TIME_TRACKING'] === 'Y',
			'time_logs' => (int)$item['TIME_SPENT_IN_LOGS'],
			'time_logs_start' => (int)$item['TIME_SPENT_IN_LOGS'],
			'time_estimate' => $item['TIME_ESTIMATE'],
			// rights
			'allow_change_deadline' => $task->isActionAllowed(CTaskItem::ACTION_CHANGE_DEADLINE),
			'allow_delegate' => $task->isActionAllowed(CTaskItem::ACTION_DELEGATE) || $canEdit,
			'allow_complete' => $task->isActionAllowed(CTaskItem::ACTION_COMPLETE),
			'allow_start' => $task->isActionAllowed(CTaskItem::ACTION_START) || $task->isActionAllowed(CTaskItem::ACTION_PAUSE),
			'allow_time_tracking' => $task->isActionAllowed(CTaskItem::ACTION_START_TIME_TRACKING),
			'allow_edit' => $canEdit,
			// dates
			'date_activity' => $activityDate,
			'date_activity_ts' => MakeTimeStamp($activityDate),
			'date_deadline' => !is_null($item['DEADLINE']) ? (MakeTimeStamp($item['DEADLINE']) - \CTimeZone::GetOffset()) : null,
			'date_deadline_parse' => ParseDateTime($item['DEADLINE']),
			'date_start' => $item['DATE_START'] != '' ? new DateTime($item['DATE_START']) : '',
			'date_view' => new DateTime($item['CREATED_DATE']),
			'date_day_end' => mktime((int)((int)$endDayTime - $this->timeOffset / 3600), 0, 0),
			// counts
			'count_comments' => 0,
			'count_files' => 0,
			'count_members' => count(array_unique(array_merge(
				$item['AUDITORS'],
				$item['ACCOMPLICES']
			))),
			'check_list' => array(
				'work' => 0,
				'complete' => 0
			),
			'log' => array(
				'comment' => 0,
				'file' => 0,
				'checklist' => 0
			),
			// statuses
			'overdue' => false,
			'is_expired' => $this->isExpired($this->getDateTimestamp($item['DEADLINE'])),
			'high' => (int)$item['PRIORITY'] === Task\Priority::HIGH,
			'new' => (int)$item['STATUS'] === MetaStatus::UNSEEN,
			'in_progress' => (int)$item['REAL_STATUS'] === Status::IN_PROGRESS,
			'deferred' => (int)$item['STATUS'] === Status::DEFERRED,
			'completed' => (int)$item['STATUS'] === Status::COMPLETED,
			'completed_supposedly' => (int)$item['STATUS'] === Status::SUPPOSEDLY_COMPLETED,
			'muted' => $item['IS_MUTED'] === 'Y',
			'item_fields' => [
				$this->displayService->fillId($item['ID']),
				$this->displayService->fillProject($item['GROUP_ID']),
				$this->displayService->fillFlow($item['FLOW_ID'] ?? 0),
				$this->displayService->fillMark(Loc::getMessage("TASKS_MARK_$markProp")),
				$this->displayService->fillDateStart($item['DATE_START'] ?? ''),
				$this->displayService->fillDateFinishPlan($item['END_DATE_PLAN'] ?? ''),
				$this->displayService->fillTimeSpent((int)$item['TIME_SPENT_IN_LOGS']),
				$this->displayService->fillCrmData($item),
				$this->displayService->fillAuditors($item['AUDITORS']),
				$this->displayService->fillAccomplices($item['ACCOMPLICES']),
			],
		);
		if ($data['date_start'])
		{
			$data['date_start'] = $data['date_start']->getTimestamp();
		}

		return $data;
	}

	/**
	 * Get one task item.
	 * @param int $id Item id.
	 * @param string|int $columnId Code of stage (if know).
	 * @param bool $bCheckPermission Check permissions.
	 * @return array
	 */
	protected function getRawData($taskId, $columnId = false, $bCheckPermission = true)
	{
		if ($columnId === null)
		{
			$columnId = false;
		}
		$row = $this->getList(array(
			'select' => $this->getSelect(),
			'filter' => array(
				'ID' => $taskId,
				'CHECK_PERMISSIONS' => $bCheckPermission ? 'Y' : 'N'
			),
			'order' => $this->getOrder()
		));
		if (!empty($row))
		{
			$row = array_pop($row);
			if (($task = $this->fillData($row)))
			{
				$task = array(
					$task['id'] => array(
						'id' => $task['id'],
						'parentId' => $task['parentId'],
						'columnId' => $task['stage_id'] > 0 ? $task['stage_id'] : $columnId,
						'data' => $task,
						'isSprintView' => ($this->arParams['SPRINT_ID'] > 0 ? 'Y' : 'N'),
						'calendarSettings' => $this->arResult['CALENDAR_SETTINGS'],
						'networkEnabled' => \Bitrix\Tasks\Integration\Network\MemberSelector::isNetworkEnabled(),
					)
				);

				// get other data
				$task = $this->getUsers($task);
				$task = $this->getNewLog($task);
				$task = $this->getFiles($task);
				$task = $this->getCheckList($task);
				$task = $this->getTags($task);
				$task = $this->getCounters($task);
				if ($this->arParams['SPRINT_ID'] > 0)
				{
					$task = $this->getScrumData($task);
				}

				$task = $this->sendEvent('TimelineComponentGetItems', $task);

				return array_pop($task);
			}
		}

		return array();
	}

	/**
	 * Returns stages by task Id.
	 * @param int $taskId
	 * @return array
	 */
	protected function getStages(int $taskId): array
	{
		$stages = [];
		$res = TaskStageTable::getList([
			'select' => [
				'STAGE_ID'
			],
			'filter' => [
				'TASK_ID' => $taskId
			]
		]);
		while ($row = $res->fetch())
		{
			$stages[] = (int)$row['STAGE_ID'];
		}

		return $stages;
	}

	protected function getTasksByStages(int ...$stageIds): array
	{
		if ([] === $stageIds)
		{
			return [];
		}

		$stages = StagesTable::query()
			->setSelect(['*']) // compatability
			->whereIn('ID', $stageIds)
			->exec()
			->fetchAll();

		return $this->getData(stages: $stages);
	}

	/**
	 * Base method for getting data.
	 * @param array $additionalFilter Additional filter.
	 * @param bool $skipCommonFilter Skip merge with common filter.
	 * @return array
	 */
	protected function getData(array $additionalFilter = [], $skipCommonFilter = false, array $stages = [])
	{
		$items = [];
		$filteredStages = [];
		$order = $this->getOrder();
		$filter = $this->getFilter();
		$listParams = $this->getListParams();
		$select = $this->getSelect();

		if ($skipCommonFilter)
		{
			$filter = $additionalFilter;
		}
		else if ($additionalFilter)
		{
			$filter = array_merge(
				$filter,
				$additionalFilter
			);
		}

		if (isset($filter['ONLY_STAGE_ID']))
		{
			$onlyStageId = (int)$filter['ONLY_STAGE_ID'];
			unset($filter['ONLY_STAGE_ID']);
		}

		if (
			StagesTable::getWorkMode() === StagesTable::WORK_MODE_GROUP
			|| StagesTable::getWorkMode() === StagesTable::WORK_MODE_ACTIVE_SPRINT
		)
		{
			if (!empty($filter['::SUBFILTER-STAGE_ID']))
			{
				$filteredStages = $filter['::SUBFILTER-STAGE_ID']['STAGE_ID'];
			}
		}


		$listParams['MAKE_ACCESS_FILTER'] = true;

		$stages = [] === $stages ? StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']) : $stages;

		// get tasks by stages
		foreach ($stages as $column)
		{
			$stageId = StagesTable::getStageIdByCode(
				$column['ID'],
				$this->arParams['STAGES_ENTITY_ID']
			);

			$realStageId = is_array($stageId) ? (int)$stageId[0] : $stageId;
			if (isset($onlyStageId) && $onlyStageId !== $realStageId)
			{
				continue;
			}

			if (!empty($filteredStages) && !in_array($realStageId, $filteredStages))
			{
				continue;
			}

			// todo have subtask then not get
			if ($column['ADDITIONAL_FILTER'])
			{
				$filterTmp = array_merge(
					$filter,
					$column['ADDITIONAL_FILTER']
				);
			}
			else
			{
				$filterTmp = array_merge(
					$filter,
					array(
						'STAGE_ID' => $stageId
					)
				);
			}

			if ($this->arParams['TIMELINE_MODE'] === 'Y' && isset($filterTmp['STAGE_ID']))
			{
				unset($filterTmp['STAGE_ID']);
			}

			[$rows, $res] = $this->getList(array(
				'select' => $select,
				'filter' => $filterTmp,
				'order' => $order,
				'navigate' => $listParams
			), true);
			// something wrong
			if (
				count($rows) > 0 && $res->NavPageNomer !=
				$listParams['NAV_PARAMS']['iNumPage']
			)
			{
				break;
			}

			foreach ($rows as $row)
			{
				$item = $this->fillData($row);
				if (!$item)
				{
					continue;
				}
				$items[$item['id']] = array(
					'id' => $item['id'],
					'parentId' => empty($item['parentId']) ? null : $item['parentId'],
					'columnId' => $column['ID'],
					'data' => $item,
					'isSprintView' => (isset($this->arParams['SPRINT_SELECTED']) && $this->arParams['SPRINT_SELECTED'] === 'Y' ? 'Y' : 'N'),
					'calendarSettings' => $this->arResult['CALENDAR_SETTINGS'],
					'networkEnabled' => \Bitrix\Tasks\Integration\Network\MemberSelector::isNetworkEnabled(),
				);
				if (
					isset($filterTmp['ID'], $item['ID'])
					&& (int)$filterTmp['ID'] === (int)$item['ID']
				)
				{
					break 2;
				}
			}
		}

		// get other data
		$items = $this->getUsers($items);
		$items = $this->getNewLog($items);
		$items = $this->getTimeStarted($items);
		$items = $this->getCounters($items);
		$items = $this->displayService->fillTags($items);
		$items = $this->displayService->fillFiles($items);
		$items = $this->displayService->fillCheckList($items);
		if (
			isset($this->arParams['SPRINT_SELECTED'])
			&& $this->arParams['SPRINT_SELECTED'] === 'Y'
		)
		{
			$items = $this->getScrumData($items);
		}

		$items = $this->sendEvent('KanbanComponentGetItems', $items);

		$this->validateCounters($items);

		return array_values($items);
	}

	/**
	 * @return bool
	 */
	protected function isMyList(): bool
	{
		return (int)$this->arParams['USER_ID'] === (int)$this->userId;
	}

	/**
	 * @param array $items
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function validateCounters(array $items): void
	{
		$userId = (int) $this->arParams['USER_ID'];
		if (!$this->isMyList())
		{
			return;
		}

		$gridValue = count($items);

		if ($gridValue > 0)
		{
			return;
		}

		$filter = $this->filterInstance->getOptions()->getFilter($this->filterInstance->getFilters());
		if (
			array_key_exists('STATUS', $filter)
			&& is_array($filter['STATUS'])
			&& in_array(Status::IN_PROGRESS, $filter['STATUS'])
		)
		{
			unset($filter['STATUS']);
		}
		unset($filter['PRESET_ID']);
		unset($filter['FILTER_ID']);

		$defaultFilter = [
			'PROBLEM' => \CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
			'FILTER_APPLIED' => true,
			'FIND' => ''
		];

		if (
			array_diff($filter, $defaultFilter)
			|| array_diff($defaultFilter, $filter)
		)
		{
			return;
		}

		$groupId = isset($this->arParams['GROUP_ID']) ? (int) $this->arParams['GROUP_ID'] : 0;

		$counter = \Bitrix\Tasks\Internals\Counter::getInstance($userId);
		$counterValue = $counter->get(\Bitrix\Tasks\Internals\Counter\CounterDictionary::COUNTER_EXPIRED, $groupId);

		if ($gridValue === $counterValue)
		{
			return;
		}

		if (\Bitrix\Tasks\Internals\Counter\Queue\Queue::isInQueue($userId))
		{
			return;
		}

		if (\Bitrix\Tasks\Internals\Counter\Event\EventTable::hasLostEvents())
		{
			return;
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob(
			['\Bitrix\Tasks\Internals\Counter\CounterController', 'recountForUser'],
			[$userId],
			Application::JOB_PRIORITY_LOW - 5
		);
	}

	/**
	 * @param array $items
	 * @return array
	 */
	protected function getCounters(array $items): array
	{
		return $this->countersService->getCounters($items);
	}

	private function getScrumData(array $items): array
	{
		$itemService = new ItemService();
		$epicService = new EpicService(\Bitrix\Tasks\Util\User::getId());

		$scrumItems = $itemService->getItemsBySourceIds(array_keys($items));

		$epicIds = [];
		foreach ($scrumItems as $item)
		{
			$items[$item->getSourceId()]['data']['storyPoints'] = $item->getStoryPoints();
			$items[$item->getSourceId()]['isVisibilitySubtasks'] = $item->getInfo()->isVisibilitySubtasks() ? 'Y' : 'N';

			$epicIds[] = $item->getEpicId();
		}

		$epicIds = array_filter(array_unique($epicIds));

		$epicsList = [];
		if ($epicIds)
		{
			$queryResult = $epicService->getList(
				[],
				['ID' => $epicIds],
				['ID' => 'ASC'],
			);
			if ($queryResult)
			{
				while ($data = $queryResult->fetch())
				{
					$epic = new EpicForm();

					$epic->fillFromDatabase($data);

					$epicsList[$epic->getId()] = $epic->toArray();
				}
			}
		}

		foreach ($scrumItems as $item)
		{
			$items[$item->getSourceId()]['data']['epic'] = array_key_exists($item->getEpicId(), $epicsList)
				? $epicsList[$item->getEpicId()]
				: []
			;
		}

		return $items;
	}

	/**
	 * Exist or not mandatory fields in the task.
	 * @return boolean
	 */
	protected function mandatoryExists()
	{
		static $result = null;

		if ($result === null)
		{
			$res = \CUserTypeEntity::getList(
				array(),
				array(
					'ENTITY_ID' => 'TASKS_TASK',
					'MANDATORY' => 'Y'
				)
			);
			if ($res->fetch())
			{
				$result = true;
			}
			else
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Set title.
	 * @return void
	 */
	protected function setTitle()
	{
		$params = $this->arParams;

		if (
			isset($params['SET_TITLE'])
			&& $params['SET_TITLE'] === 'Y'
		)
		{
			if ($this->arResult['IS_COLLAB'])
			{
				$this->application->setTitle(Loc::getMessage('TASK_LIST_TITLE'));
			}
			elseif (
				isset($params['PROJECT_VIEW'])
				&& $params['PROJECT_VIEW'] === 'Y'
			)
			{
				$this->application->setTitle(Loc::getMessage('TASK_PROJECT_TITLE'));
			}
			else if (
				isset($params['TIMELINE_MODE'])
				&& $params['TIMELINE_MODE'] === 'Y'
			)
			{
				$this->application->setTitle(Loc::getMessage('TASK_LIST_TITLE_TIMELINE'));
			}
			else if ($this->itsMyTasks())
			{
				$this->application->setTitle(Loc::getMessage('TASK_KANBAN'.($params['PERSONAL']!='N'?'_PERSONAL':'').'_TITLE'));
			}
			elseif (
				isset($params['SPRINT_SELECTED'])
				&& $params['SPRINT_SELECTED'] === 'Y'
			)
			{
				$this->application->setTitle(Loc::getMessage('TASK_LIST_TITLE_SPRINT'));
			}
			elseif (
				$this->taskType == static::TASK_TYPE_GROUP
				&& (
					!array_key_exists('GROUP_ID_FORCED', $params)
					|| !$params['GROUP_ID_FORCED']
				)
			)
			{
				$this->application->setTitle(Loc::getMessage('TASK_LIST_TITLE_GROUP'));
			}
			else
			{
				$userName = \CUser::FormatName($params['~NAME_TEMPLATE'], $this->arResult['USER'], true, false);
				$userName = \htmlspecialcharsbx($userName);
				$this->application->setPageProperty('title', $userName . ': ' . Loc::getMessage('TASK_KANBAN'.($params['PERSONAL']!='N'?'_PERSONAL':'').'_TITLE'));
				$this->application->setTitle(Loc::getMessage('TASK_KANBAN'.($params['PERSONAL']!='N'?'_PERSONAL':'').'_TITLE'));
			}
		}
	}

	/**
	 * Check views for current Kanban. Ask admin for create default.
	 * @return boolean
	 */
	protected function checkViews()
	{
		$params = $this->arParams;
		$this->arResult['MP_CONVERTER'] = 0;

		// for sprint's kanban create by default
		if ($params['IS_SCRUM'])
		{
			return true;
		}

		// for personal - create defaults and copy tasks to default stage
		if ($params['PERSONAL'] == 'Y')
		{
			$checkStages = StagesTable::getStages($params['STAGES_ENTITY_ID'], true);
			if ($params['TIMELINE_MODE'] == 'Y')
			{
				return true;
			}
			if (empty($checkStages))
			{
				$this->arResult['MP_CONVERTER'] = 1;
			}
			// for new versions of personal we need to insert sone new tasks
			else
			{
				$userVersion = \CUserOptions::getOption(
					'tasks',
					'personal_kanban_version',
					1,
					$params['STAGES_ENTITY_ID']
				);
				if ($userVersion < StagesTable::MY_PLAN_VERSION)
				{
					$this->arResult['MP_CONVERTER'] = 1;
				}
			}
			return true;
		}

		// stages is empty - ask admin for copy stages
		if ($this->isAdmin() && $params['SONET_LOAD'])
		{
			$checkStages = StagesTable::getStages($params['STAGES_ENTITY_ID'], true);
			if (empty($checkStages))
			{
				if ($params['IS_AJAX'] === 'Y')
				{
					return false;
				}

				if (
					$this->taskType == static::TASK_TYPE_GROUP &&
					!\CSocNetGroup::GetByID($params['GROUP_ID'])
				)
				{
					return true;
				}

				$views = array();
				if (empty($views))
				{
					// create default view, if not exists
					StagesTable::getStages(0);
					// get all stages views
					$res = StagesTable::getList(array(
						'select' => array(
							'ENTITY_ID'
						),
						'filter' => array(
							'=ENTITY_TYPE' => StagesTable::WORK_MODE_GROUP
						),
						'group' => array(
							'ENTITY_ID'
						)
					));
					while ($row = $res->fetch())
					{
						$views[$row['ENTITY_ID']] = array();
					}
					// get current user groups
					if (!empty($views))
					{
						$res = \CSocNetUserToGroup::GetList(
							array(
								'GROUP_DATE_ACTIVITY' => 'DESC'
							),
							array(
								'GROUP_ID' => array_keys($views),
								'USER_ID' => $this->userId
							),
							false, false,
							array(
								'ID', 'GROUP_NAME', 'GROUP_ID' , 'GROUP_SCRUM_MASTER_ID'
							)
						);
						while ($row = $res->fetch())
						{
							if (
								$this->canReadGroupTasks($row['GROUP_ID'])
								&& (empty($row['GROUP_SCRUM_MASTER_ID']))
							)
							{
								$views[$row['GROUP_ID']] = array(
									'ID' => $row['GROUP_ID'],
									'NAME' => $row['GROUP_NAME']
								);
							}
						}
					}
					// check disable views for current user
					$viewsTmp = array();
					foreach ($views as $vId => $view)
					{
						if (!empty($view))
						{
							$viewsTmp[$vId] = $view;
						}
					}
					$views = $viewsTmp;
					unset($viewsTmp);
					// if view not exists - copy from default
					if (empty($views))
					{
						StagesTable::copyView(0, $params['GROUP_ID']);
						$this->reloadPage();
					}
					// if user submit view, what he want
					$setView = $this->request('set_view_id');
					if ($setView !== null && ($setView == 0 || isset($views[$setView])))
					{
						StagesTable::copyView($setView, $params['GROUP_ID']);
						$this->reloadPage();
					}
					// if default
					if ($params['GROUP_ID'] == 0)
					{
						$this->reloadPage();
					}
					// else ask user
					$this->arResult['VIEWS'] = $views;
					$this->arResult['DATA'] = array(
						'columns' => $this->getDemoColumns(0),
						'items' => array(),
						'demo' => true
					);
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get order for new tasks.
	 * @return string
	 */
	protected function getNewTaskOrder()
	{
		$groupId = $this->arParams['GROUP_ID'];

		if ($groupId > 0 && $this->arParams['PERSONAL'] != 'Y')
		{
			if (($row = ProjectsTable::getById($groupId)->fetch()))
			{
				return $row['ORDER_NEW_TASK'] ? $row['ORDER_NEW_TASK'] : 'actual';
			}
			else
			{
				return 'actual';
			}
		}
		else
		{
			return \CUserOptions::getOption('tasks', static::ORDER_OPTION, 'actual');
		}
	}

	/**
	 * Init arResult.
	 * @return void
	 */
	protected function initResult()
	{
		$this->arResult['ERRORS'] = $this->getErrorsOld();
		$this->arResult['ERRORS_COLLECTION'] = $this->getErrorsOld(false);
		$this->arResult['TASK_TYPE'] = $this->taskType;
		$this->arResult['PAGE'] = $this->getURI();
		$this->arResult['CURRENT_USER_ID'] = $this->userId;
		$this->arResult['ACCESS_CONFIG_PERMS'] = $this->isAdmin();
		$this->arResult['ACCESS_CREATE_PERMS'] = $this->canCreateTasks();
		$this->arResult['ACCESS_SORT_PERMS'] = $this->canSortTasks();
		$this->arResult['NEW_TASKS_ORDER'] = $this->getNewTaskOrder();
		$this->arResult['ITS_MY_TASKS'] = $this->itsMyTasks();
		$this->arResult['TOURS'] = $this->processTours();
		$this->arResult['ADMINS'] = [];
		$this->arResult['CONTEXT'] = $this->arParams['CONTEXT'] ?? Context::getDefault();
		if (!$this->isScrum())
		{
			// Kanban User Selected Fields
			$this->arResult['POPUP_FIELDS_SECTIONS'] = $this->kanbanUserSettings->getPopupSections();
		}

		$this->arResult['DEFAULT_PRESET_KEY'] = (
			$this->filterInstance ? $this->filterInstance->getDefaultPresetKey() : ''
		);

		if (
			!array_key_exists('VIEWS', $this->arResult)
			|| !$this->arResult['VIEWS']
		)
		{
			$this->arResult['VIEWS'] = array();
		}

		if (
			!array_key_exists('ACCESS_CONFIG_PERMS', $this->arResult)
			|| !$this->arResult['ACCESS_CONFIG_PERMS']
		)
		{
			$this->arResult['ADMINS'] = $this->getAdmins();
		}

		$this->arResult['IS_COLLAB'] = isset($this->arResult['CONTEXT']) && $this->arResult['CONTEXT'] === Context::getCollab();
		if ($this->arResult['IS_COLLAB'])
		{
			$group = SocialNetwork\Group::getGroupData((int) $this->arParams['GROUP_ID']);
			$this->arResult["COLLAB_NAME"] = $group["NAME"] ?? '';
			$this->arResult["COLLAB_IMAGE"] = $group["IMAGE"] ?? '';
		}
	}

	private function processTours(): array
	{
		$tours = [
			'firstTimelineTaskCreation' => [
				'show' => false,
				'popupData' => [],
			],
			'expiredTasksDeadlineChange' => [
				'show' => false,
				'popupData' => [],
				'backgroundCheck' => false,
			],
		];

		if ($this->canProceedTours())
		{
			/** @var TourGuide\FirstTimelineTaskCreation $firstTimelineTaskTour */
			$firstTimelineTaskTour = TourGuide\FirstTimelineTaskCreation::getInstance($this->userId);
			$currentStepPopupData = $firstTimelineTaskTour->getCurrentStepPopupData();
			$showFirstTimelineTaskTour = $firstTimelineTaskTour->proceed();

			$tours['firstTimelineTaskCreation'] = [
				'show' => $showFirstTimelineTaskTour,
				'popupData' => $currentStepPopupData,
			];

			if (!$showFirstTimelineTaskTour && $this->canProceedExpiredTour())
			{
				/** @var TourGuide\ExpiredTasksDeadlineChange $expiredTour */
				$expiredTour = TourGuide\ExpiredTasksDeadlineChange::getInstance($this->userId);
				$currentStepPopupData = $expiredTour->getCurrentStepPopupData();
				$showExpiredTour = $expiredTour->proceed();

				$tours['expiredTasksDeadlineChange'] = [
					'show' => $showExpiredTour,
					'popupData' => $currentStepPopupData,
					'backgroundCheck' => !$showExpiredTour && $expiredTour->canPotentiallyProceed(),
					'counterToCheck' => $expiredTour->getNeededExpiredTasksCount(),
				];
			}
		}

		return $tours;
	}

	private function canProceedTours(): bool
	{
		return $this->isMyList() && !$this->request->isAjaxRequest();
	}

	private function canProceedExpiredTour(): bool
	{
		return false;

		if ($this->arParams['GROUP_ID'] > 0)
		{
			return false;
		}

		$isFilteredByExpired = false;

		if ($filterOptions = $this->filterInstance->getOptions())
		{
			$filterFields = $filterOptions->getFilter($this->filterInstance->getFilters());
			$isFilteredByExpired =
				array_key_exists('PROBLEM', $filterFields)
				&& (int)$filterFields['PROBLEM'] === Counter\Type::TYPE_EXPIRED
			;
		}

		if ($isFilteredByExpired)
		{
			return false;
		}

		return !Counter\Queue\Queue::isInQueue($this->userId);
	}

	/**
	 * Checks session key.
	 */
	protected function checkSessionDataKey()
	{
		if (
			!isset($_SESSION[$this::SESS_DATA_KEY]) ||
			!is_array($_SESSION[$this::SESS_DATA_KEY])
		)
		{
			$_SESSION[$this::SESS_DATA_KEY] = [];
		}
	}

	/**
	 * Stores data in session.
	 * @param string $key Data key.
	 * @param mixed $value Key payload.
	 * @return void
	 */
	protected function setSessionData($key, $value)
	{
		if (!is_string($key) && !is_int($key))
		{
			return;
		}
		$this->checkSessionDataKey();
		$_SESSION[$this::SESS_DATA_KEY][$key] = $value;
	}

	/**
	 * Returns data from session by key.
	 * @param string $key Data key.
	 * @return mixed
	 */
	protected function getSessionData($key)
	{
		if (is_string($key) || is_int($key))
		{
			$this->checkSessionDataKey();
			if (array_key_exists($key, $_SESSION[$this::SESS_DATA_KEY]))
			{
				return $_SESSION[$this::SESS_DATA_KEY][$key];
			}
		}
		return null;
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		$init = $this->init();

		// if ajax, go to the actions
		if ($this->arParams['IS_AJAX'] === 'Y')
		{
			$this->executeComponentAjax();
			return;
		}

		$this->arResult['NEED_SET_CLIENT_DATE'] = true;
		/*$clientTimeTTL = $this->getSessionData('clientTimeTTL');
		$clientTimeStored = $this->getSessionData('clientTimeStored');
		$this->arResult['NEED_SET_CLIENT_DATE'] = (time() > $clientTimeTTL) ||
													(time() - $clientTimeStored > 600);*/

		if (!$this->arResult['NEED_SET_CLIENT_DATE'])
		{
			TimeLineTable::setDateClient(
				$this->getSessionData('clientDate')
			);
			TimeLineTable::setDateTimeClient(
				$this->getSessionData('clientTime')
			);
		}

		$this->arResult['MANDATORY_EXISTS'] = $this->mandatoryExists();

		// if all right, get data
		if ($init && $this->checkViews())
		{
			$columns = $this->getColumns();

			$this->arResult['DATA'] = array(
				'columns' => $columns,
				'items' => $this->getData(),
				'demo' => false,
			);
		}

		// if need converter, check current task count
		if (isset($this->arResult['MP_CONVERTER']) && $this->arResult['MP_CONVERTER'] > 0)
		{
			$res = \CTasks::GetList(array(
				//
			), array(
				'MEMBER' => $this->arParams['STAGES_ENTITY_ID']
			), array(
				'ID'
			), array(
				'nPageSize' => 1
			));
			if ($res->fetch())
			{
				$this->arResult['MP_CONVERTER'] = 1;//some bug in tasks
			}
			else
			{
				$this->arResult['MP_CONVERTER'] = 0;
				\CUserOptions::setOption(
					'tasks',
					'personal_kanban_version',
					StagesTable::MY_PLAN_VERSION,
					false,
					$this->arParams['STAGES_ENTITY_ID']
				);
			}
		}

		$this->arResult['FATAL'] = !$init;

		$this->initResult();
		$this->setTitle();
		$this->IncludeComponentTemplate();
	}

	/**
	 * Ajax-hit, make some actions.
	 * @return void
	 */
	public function executeComponentAjax()
	{
		$fatal = false;
		// call action
		if ($this->init())
		{
			if (check_bitrix_sessid())
			{
				$action = $this->request('action');
				if ($action && is_callable(array($this, 'action' . $action)))
				{
					try
					{
						$this->setSessionData('clientTimeTTL', 0);
						$return = $this->{'action' . $action}();
					}
					catch (TasksException $e)
					{
						$fatal = true;
						$return = array();
						$this->addError($e->getMessage());
					}

				}
			}
			else
			{
				$fatal = true;
				$return = array();
				$this->addError('TASK_LIST_SESS_EXPIRED');
			}
		}
		if (!isset($return))
		{
			$fatal = true;
			$return = array();
			$this->addError('TASK_LIST_UNKNOWN_ACTION');
		}
		// if is array, output as a json
		if (is_array($return))
		{
			if ($errors = $this->getErrorsOld())
			{
				$return = array(
					'error' => implode("\n", $errors),
					'fatal' => $fatal
				);
			}
			$this->application->RestartBuffer();
			header('Content-Type: application/json');
			echo \Bitrix\Main\Web\Json::encode($return);
		}
		// else simple html
		else
		{
			echo $return;
		}
	}

	/**
	 * Set sorting to task.
	 * @param int $taskId
	 * @param int $beforeId
	 * @param int $afterId
	 * @return void
	 */
	protected function setSorting($taskId, $beforeId, $afterId = 0, $userId = false)
	{
		if ($userId === false)
		{
			$userId = $this->userId;
		}
		if ($beforeId > 0)
		{
			Task\SortingTable::setSorting(
				$userId,
				$this->arParams['GROUP_ID'],
				$taskId,
				$beforeId,
				true
			);
		}
		elseif ($afterId > 0)
		{
			Task\SortingTable::setSorting(
				$userId,
				$this->arParams['GROUP_ID'],
				$taskId,
				$afterId,
				false
			);
		}
	}

	protected function checkBeforeAction(): bool
	{
		if (!$this->init())
		{
			return false;
		}

		return true;
	}

	/**
	 * Add new task.
	 */
	public function addTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$columnId = $this->request('columnId');
		$taskName = $this->request('taskName');
		if (!$columnId || trim($taskName) === '')
		{
			return [];
		}

		if ($this->mandatoryExists())
		{
			$this->addError('TASK_LIST_TASK_MANDATORY_EXISTS');

			return [];
		}

		if (!$this->canCreateTasks())
		{
			$this->addError('TASK_LIST_TASK_CREATE_DENIED_V2');

			return [];
		}

		$params = $this->arParams;

		$scrumProject = false;

		$responsibleId = $params['USER_ID'];
		if (
			StagesTable::getWorkMode() === StagesTable::WORK_MODE_ACTIVE_SPRINT
			&& Loader::includeModule('socialnetwork')
		)
		{
			$scrumManager = new ScrumManager($params['GROUP_ID']);
			$responsibleId = $scrumManager->getScrumTaskResponsible($params['USER_ID']);
			$scrumProject = true;
		}

		$stages = StagesTable::getStages($params['STAGES_ENTITY_ID']);
		$fields = [
			'TITLE' => $taskName,
			'CREATED_BY' => $this->userId,
			'RESPONSIBLE_ID' => $responsibleId,
			'GROUP_ID' => $params['GROUP_ID'],
			'STAGE_ID' => isset($stages[$columnId]) ? $columnId : 0,
		];

		if ($scrumProject)
		{
			$parentTaskId = (int)$this->request('parentTaskId');
			if ($parentTaskId)
			{
				try
				{
					$taskItemObject = CTaskItem::getInstance($parentTaskId, $this->userId);
					$taskInfo = $taskItemObject->getData(false, [
						'select' => ['TAGS'],
					]);
					$fields['PARENT_ID'] = $parentTaskId;
					$fields['TAGS'] = $taskInfo['TAGS'] ? : [];
				}
				catch (Exception $exception)
				{
					LogFacade::logThrowable($exception);
					$this->addError('TASK_LIST_TASK_MANDATORY_EXISTS');
					return [];
				}
			}
		}

		if (isset($stages[$columnId]['TO_UPDATE']))
		{
			$fields = array_merge(
				$fields,
				$stages[$columnId]['TO_UPDATE']
			);
		}
		if (
			$params['PERSONAL'] === 'Y'
			|| $params['SPRINT_ID'] > 0
		)
		{
			unset($fields['STAGE_ID']);
		}
		// disable sort / link - its set below here
		if ((int)$params['GROUP_ID'] === 0)
		{
			StagesTable::disablePinForUser($this->userId);
		}
		if (
			$params['PERSONAL'] === 'Y'
			&& $params['TIMELINE_MODE'] !== 'Y'
		)
		{
			StagesTable::disableLinkForUser($params['USER_ID']);
		}
		$task = CTaskItem::add($fields, $this->userId, [
			'DISABLE_BIZPROC_RUN' => true,
			'SKIP_TIMEZONE' => [
				'DEADLINE',
			]
		]);
		if ($task->getId() > 0)
		{
			$newId = $task->getId();
			// set link
			if (
				($params['PERSONAL'] === 'Y'
					|| $params['SPRINT_ID'] > 0)
				&& $params['TIMELINE_MODE'] !== 'Y'
			)
			{
				if ($params['SPRINT_ID'] > 0)
				{
					$kanbanService = new KanbanService();
					$kanbanService->removeTasksFromKanban($params['SPRINT_ID'], [$newId]);
				}
				TaskStageTable::add([
					'TASK_ID' => $newId,
					'STAGE_ID' => $columnId,
				]);
			}
			// set sort
			if ((int)$params['GROUP_ID'] === 0)
			{
				$this->setSorting(
					$newId,
					$this->request('beforeItemId'),
					$this->request('afterItemId')
				);
			}

			try
			{
				Listener::onTaskAdd($newId, $task->getData());
			}
			catch (TasksException $e)
			{
				LogFacade::logThrowable($e);
			}

			if ($scrumProject)
			{
				$this->updateScrumItem($params['SPRINT_ID'], $newId, $fields);

				$taskObject = new \CTasks();
				$taskObject->update($newId, ['STAGE_ID' => $columnId]);
			}

			// output
			return $this->getRawData($newId, $columnId);
		}

		if (($e = $this->application->GetException()))
		{
			$this->addError($e->getString());
		}

		return [];
	}

	private function updateScrumItem(int $sprintId, int $taskId, array $fields): void
	{
		try
		{
			$itemService = new ItemService();
			$pushService = (Loader::includeModule('pull') ? new PushService() : null);

			$taskItem = new ItemForm();

			$taskItem->setSourceId($taskId);
			$taskItem->setEntityId($sprintId);
			$taskItem->setCreatedBy($fields['CREATED_BY']);

			$createdItem = $itemService->getItemBySourceId($taskId);
			$taskItem->setId($createdItem->getId());

			$itemService->changeItem($taskItem, $pushService);
			if ($itemService->getErrors())
			{
				$this->addError($itemService->getErrors()[0]);
			}
		}
		catch(Exception $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

	/**
	 * Move item from one stage to another.
	 * @return array
	 */
	public function moveTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$columnId = $this->request('columnId');
		$taskId = $this->request('itemId');
		if (!$taskId)
		{
			$taskId = $this->request('taskId');
		}
		if ($taskId && $columnId && $this->canSortTasks())
		{
			$groupAction = $this->request('groupAction') === 'Y';
			$stages = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);

			if (isset($stages[$columnId]))
			{
				$task = new \CTasks;
				$taskIds = (array) $taskId;
				foreach ($taskIds as $taskId)
				{
					$rows = $this->getData(
						['ID' => $taskId],
						true
					);
					foreach ($rows as $data)
					{
						$beforeItemId = $this->request('beforeItemId');
						$afterItemId = $this->request('afterItemId');
						if ($beforeItemId || $afterItemId)
						{
							$this->setSorting(
								$data['id'],
								$this->request('beforeItemId'),
								$this->request('afterItemId')
							);
						}
						else
						{
							Task\SortingTable::deleteByTaskId($data['id']);
						}

						// if is the same column, just sorting
						if ($data['columnId'] == $columnId)
						{
							continue;
						}

						// update some fields, if fields is exists
						if (isset($stages[$columnId]['TO_UPDATE']))
						{
							$acceesAllowed = true;
							if ($stages[$columnId]['TO_UPDATE_ACCESS'])
							{
								$taskInst = CTaskItem::getInstance($taskId, $this->userId);
								if (!$taskInst->checkAccess(ActionDictionary::getActionByLegacyId($stages[$columnId]['TO_UPDATE_ACCESS'])))
								{
									$acceesAllowed = false;
								}
							}
							if ($acceesAllowed)
							{
								$task->update(
									$data['id'],
									$stages[$columnId]['TO_UPDATE']
								);
							}
							else if (!$groupAction)
							{
								$this->addError('TASK_LIST_TASK_ACTION_DENIED_V2');
								return [];
							}
						}

						// if is timeline, we don't have real columns
						if ($this->arParams['TIMELINE_MODE'] === 'Y')
						{
							continue;
						}

						// personal kanban or scrum
						if (
							$this->arParams['PERSONAL'] === 'Y'
							|| $this->arParams['SPRINT_ID'] > 0
						)
						{
							if ($this->arParams['PERSONAL'] === 'Y')
							{
								StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
							}
							else
							{
								StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);
							}
							$resStg = TaskStageTable::getList(array(
								'filter' => array(
									'TASK_ID' => $data['id'],
									'=STAGE.ENTITY_TYPE' => StagesTable::getWorkMode(),
									'STAGE.ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
								)
							));
							while ($rowStg = $resStg->fetch())
							{
								TaskStageTable::update($rowStg['ID'], array(
									'STAGE_ID' => $columnId
								));

								if (
									$this->arParams['PERSONAL'] === 'Y'
									&& $columnId !== $rowStg['STAGE_ID']
								)
								{
									Listener::onPlanTaskStageUpdate(
										$this->arParams['STAGES_ENTITY_ID'],
										$rowStg['TASK_ID'],
										$columnId
									);
								}
							}
							if (StagesTable::getWorkMode() === StagesTable::WORK_MODE_ACTIVE_SPRINT)
							{
								if ($stages[$columnId]['SYSTEM_TYPE'] === StagesTable::SYS_TYPE_FINISH)
								{
									$this->completeTask($taskId);
								}
								else
								{
									$this->renewTask($taskId);
								}
								StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

								$task->update($data['id'], array(
									'STAGE_ID' => $columnId
								));
								\Bitrix\Tasks\Integration\Pull\PushService::addEvent($this->getMembersTask($data), [
									'module_id' => 'tasks',
									'command' => PushCommand::TASK_STAGE_UPDATED,
									'params' => [
										'taskId' => $data['id']
									]
								]);
							}
						}
						// or common
						else
						{
							$task->update($data['id'], array(
								'STAGE_ID' => $columnId
							));
							\Bitrix\Tasks\Integration\Pull\PushService::addEvent($this->getMembersTask($data), [
								'module_id' => 'tasks',
								'command' => PushCommand::TASK_STAGE_UPDATED,
								'params' => [
									'taskId' => $data['id']
								]
							]);
						}
					}

					if (!$groupAction)
					{
						$rows = $this->getData(
							['ID' => $taskId],
							true
						);
						if ($rows)
						{
							$rows = array_shift($rows);
							// additional flag, if user can't see this task by his filter
							if (!$this->getData(['ID' => $taskId]))
							{
								$rows['data']['hiddenByFilter'] = true;
							}
							return $rows;
						}
					}
				}
			}
		}

		return array();
	}

	public function completeParentTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$taskId = (int)$this->request('taskId');
		$finishColumnId = $this->request('finishColumnId');

		$stages = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);

		if (!$taskId || !isset($stages[$finishColumnId]))
		{
			return [];
		}

		$rows = $this->getData(['ID' => $taskId], true);
		$taskData = array_shift($rows);

		$taskStageTable = TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $taskData['id'],
				'=STAGE.ENTITY_TYPE' => StagesTable::getWorkMode(),
				'STAGE.ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
			]
		]);
		while ($taskStage = $taskStageTable->fetch())
		{
			TaskStageTable::update($taskStage['ID'], ['STAGE_ID' => $finishColumnId]);
		}

		$this->completeTask($taskId);

		// bizproc set with mode to another value...
		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		return [];
	}

	public function renewParentTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$taskId = (int)$this->request('taskId');
		$newColumnId = $this->request('newColumnId');

		$stages = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);

		if (!$taskId || !isset($stages[$newColumnId]))
		{
			return [];
		}

		$rows = $this->getData(['ID' => $taskId], true);
		$taskData = array_shift($rows);

		$taskStageTable = TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $taskData['id'],
				'=STAGE.ENTITY_TYPE' => StagesTable::getWorkMode(),
				'STAGE.ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID'],
			]
		]);
		while ($taskStage = $taskStageTable->fetch())
		{
			TaskStageTable::update($taskStage['ID'], ['STAGE_ID' => $newColumnId]);
		}

		$this->renewTask($taskId);

		// bizproc set with mode to another value...
		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		return [];
	}

	public function proceedParentTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$taskId = (int)$this->request('taskId');

		if (!$taskId)
		{
			return [];
		}

		$this->addFilter('ID', $taskId);
		$data = $this->getData();
		if ($data)
		{
			return array_pop($data);
		}

		return [];
	}

	/**
	 * Get new task info.
	 * @return array
	 */
	public function newTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (($taskId = $this->request('taskId')))
		{
			$this->addFilter('ID', $taskId);
			$data = $this->getData();
			if ($data)
			{
				return array_pop($data);
			}
		}

		return array();
	}

	public function refreshListTasksAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (($taskIds = $this->request('taskIds')))
		{
			$isScrum = $this->request('isScrum') === 'Y';
			if ($isScrum)
			{
				$taskIds = $this->sortTaskIdsByScrumFilter(
					$taskIds,
					(int) $this->request('parentId'),
					$this->arParams['GROUP_ID'],
					$this->arParams['USER_ID']
				);
				if (empty($taskIds))
				{
					return [];
				}
			}

			if ($this->arParams['TIMELINE_MODE'] === 'Y')
			{
				return $this->getData(['ID' => $taskIds], true);
			}

			$rows = $this->getData(['ID' => $taskIds]);
			foreach ($rows as $key => $row)
			{
				$rows[$key]['columns'] = $this->getStages($row['id']);
			}

			return $rows;
		}

		return [];
	}

	/**
	 * Just refresh task info.
	 * @return array
	 */
	public function refreshTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (($taskId = $this->request('taskId')))
		{
			$isScrum = $this->request('isScrum') === 'Y';
			if ($isScrum)
			{
				$taskIds = $this->sortTaskIdsByScrumFilter(
					[$taskId],
					(int) $this->request('parentId'),
					$this->arParams['GROUP_ID'],
					$this->arParams['USER_ID']
				);
				$taskId = empty($taskIds) ? null : current($taskIds);
				if (!$taskId)
				{
					return [];
				}
			}

			if ($this->arParams['TIMELINE_MODE'] === 'Y')
			{
				$rows = $this->getData(
					['ID' => $taskId],
					true
				);
				if ($rows)
				{
					return array_shift($rows);
				}
			}
			else
			{
				$rows = $this->getData(['ID' => $taskId]);
				$data = array_shift($rows);
				if (!empty($data))
				{
					$data['columns'] = $this->getStages($taskId);

					return $data;
				}
			}
		}

		return [];
	}

	/**
	 * Get items from one column.
	 * @return array
	 */
	public function getColumnItemsAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$items = [];

		if (($columnId = $this->request('columnId')))
		{
			$this->addFilter('ONLY_STAGE_ID', $columnId);
			if (($pageId = $this->request('pageId')))
			{
				$this->setPageId($pageId);
			}

			$items = $this->getData();

			$isSprintView = (($this->arParams['SPRINT_SELECTED'] ?? null) === 'Y');
			if ($isSprintView)
			{
				$scrumManager = new ScrumManager($this->arParams['GROUP_ID']);

				$mapOfExistenceOfSubtasks = $scrumManager->buildMapOfExistenceOfSubtasks(
					$this->arParams['SPRINT_ID'],
					array_column($items, 'id')
				);

				foreach ($items as $key => $item)
				{
					$item['isParentTask'] = (!empty($mapOfExistenceOfSubtasks[$item['id']]));
					$item['isSubTask'] = ($item['parentId'] !== null);

					$items[$key] = $item;
				}

				return $items;
			}
		}

		return $items;
	}

	/**
	 * Modify stage in Kanban.
	 * @return array
	 */
	public function modifyColumnAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if ($this->arParams['TIMELINE_MODE'] === 'Y')
		{
			$this->addError('TASK_LIST_TASK_ACTION_DENIED_V2');
			return [];
		}

		$fields = $this->request('fields');
		if (is_array($fields))
		{
			if ($this->isAdmin())
			{
				// check rights
				if (isset($fields['id']))
				{
					$stages = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);
					if (!isset($stages[$fields['id']]))
					{
						unset($fields['id']);
					}
				}
				// delete
				if (
					isset($fields['id'])
					&& isset($fields['delete'])
					&& $fields['delete']
				)
				{
					if ($this->arParams['PERSONAL'] === 'Y')
					{
						$stageId = StagesTable::getStageIdByCode(
							$fields['id'],
							$this->arParams['STAGES_ENTITY_ID']
						);
						$rows = TaskStageTable::getList(array(
							'filter' => array(
								'STAGE_ID' => $stageId,
								'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
								'STAGE.ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
							)
						))->fetch();
						// check for old refs
						if (!empty($rows))
						{
							$rows = $this->getList(array(
								'select' => array(
									'ID'
								),
								'navigate' => array(
									'NAV_PARAMS' => array(
										'nTopCount' => 1
									)
								),
								'filter' => array(
									'STAGES_ID' => $stageId,
									'MEMBER' => $this->arParams['STAGES_ENTITY_ID']
								)
							));
						}
					}
					else
					{
						$rows = $this->getList(array(
							'select' => array(
								'ID'
							),
							'navigate' => array(
								'NAV_PARAMS' => array(
									'nTopCount' => 1
								)
							),
							'filter' => array(
								'STAGE_ID' => StagesTable::getStageIdByCode(
									$fields['id'],
									$this->arParams['STAGES_ENTITY_ID']
								),
								'GROUP_ID' => $this->arParams['GROUP_ID'],
								'CHECK_PERMISSIONS' => 'N'
							)
						));
					}
					if (empty($rows))
					{
						$res = StagesTable::delete(
							$fields['id'],
							$this->arParams['STAGES_ENTITY_ID']
						);
						if (!$res->isSuccess())
						{
							$this->copyError($res->getErrors());
						}
					}
					else
					{
						$this->addError('TASK_LIST_COLUMN_NOT_EMPTY');
					}
				}
				// add / update
				else if (isset($fields['columnName']) && trim($fields['columnName']) != '')
				{
					$fields = array(
						'ID' => isset($fields['id']) ? (int)$fields['id'] : 0,
						'COLOR' => $fields['columnColor'] ?? '',
						'TITLE' => $fields['columnName'],
						'AFTER_ID' => $fields['afterColumnId'] ?? null,
						'ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
					);
					if ($fields['AFTER_ID'] === null)
					{
						unset($fields['AFTER_ID']);
					}
					$res = StagesTable::updateByCode($fields['ID'], $fields);
					if ((int)$fields['ID'] === 0 && $res && $res->isSuccess())
					{
						$filter = $this->getFilter();
						if (
							!empty($filter['::SUBFILTER-STAGE_ID'])
							&& is_array($filter['::SUBFILTER-STAGE_ID']['STAGE_ID'])
						)
						{
							$this->filter['::SUBFILTER-STAGE_ID']['STAGE_ID'][] = (string)$res->getId();
						}

						$columns = $this->getColumns(true);

						return is_array($columns[$res->getId()]) ? $columns[$res->getId()] : [];
					}
				}
				else
				{
					$this->addError('TASK_LIST_COLUMN_TITLE_EMPTY');
				}
			}
			else
			{
				$this->addError('TASK_LIST_TASK_ACTION_DENIED_V2');
			}
		}
		return array();
	}

	/**
	 * Move column.
	 * @return array
	 */
	public function moveColumnAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if ($this->arParams['TIMELINE_MODE'] === 'Y')
		{
			$this->addError('TASK_LIST_TASK_ACTION_DENIED_V2');
		}
		else if ($this->isAdmin() && ($columnId = $this->request('columnId')))
		{
			$afterColumnId = $this->request('afterColumnId');
			StagesTable::updateByCode($columnId, array(
				'AFTER_ID' => $afterColumnId,
				'ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
			));
		}
		else
		{
			$this->addError('TASK_LIST_TASK_ACTION_DENIED_V2');
		}

		return array();
	}

	/**
	 * Apply filter and restart.
	 * @return array
	 */
	public function applyFilterAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$columns = $this->getColumns();

		$items = $this->getData();
		$parentTasks = [];

		$groupId = (int) $this->arParams['GROUP_ID'];

		$scrumManager = new ScrumManager($groupId);
		if ($scrumManager->isScrumProject())
		{
			$taskRegistry = TaskRegistry::getInstance();

			[$items, $columns, $parentTasks] = $scrumManager->groupBySubTasks($taskRegistry, $items, $columns);
		}

		return [
			'columns' => $columns,
			'items' => $items,
			'parentTasks' => $parentTasks,
		];
	}

	public function changeSprintAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$groupId = (int) $this->arParams['GROUP_ID'];
		$sprintId = (int) $this->arParams['SPRINT_ID'];

		StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

		$this->arParams['SPRINT_SELECTED'] = 'Y';
		$this->arParams['STAGES_ENTITY_ID'] = $sprintId;

		$columns = $this->getColumns();
		$items = $this->getData();
		$parentTasks = [];

		$scrumManager = new ScrumManager($groupId);
		if ($scrumManager->isScrumProject())
		{
			$taskRegistry = TaskRegistry::getInstance();

			[$items, $columns, $parentTasks] = $scrumManager->groupBySubTasks($taskRegistry, $items, $columns);
		}

		return [
			'columns' => $columns,
			'items' => $items,
			'parentTasks' => $parentTasks
		];
	}

	/**
	 * Set client date and time.
	 * @return array
	 */
	public function setClientDateAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$clientDate = $this->request('clientDate');
		$clientTime = $this->request('clientTime');

		// calc ttl for this data
		$clientTimeTS = \MakeTimeStamp($clientTime);
		$clientTimeTTL = time() +
						 (24 - 1 - date('H', $clientTimeTS)) * 3600 +
						 (60 - date('i', $clientTimeTS)) * 60 + 60;

		// save in session
		$this->setSessionData('clientDate', $clientDate);
		$this->setSessionData('clientTime', $clientTime);
		$this->setSessionData('clientTimeTTL', $clientTimeTTL);
		$this->setSessionData('clientTimeStored', time());

		// set for timeline stages
		TimeLineTable::setDateClient(
			$this->request('clientDate')
		);
		TimeLineTable::setDateTimeClient(
			$this->request('clientTime')
		);

		return array(
			'columns' => $this->getColumns(),
			'items' => $this->getData()
		);
	}

	/**
	 * Change demo view to other.
	 * @return array
	 */
	public function changeDemoViewAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$viewId = (int)$this->request('viewId');

		return array(
			'columns' => $this->canReadGroupTasks($viewId)
						? $this->getDemoColumns($viewId)
						: array(),
			'items' => array(),
			'demo' => true
		);
	}

	/**
	 * Notify admin for get access.
	 * @return array
	 */
	public function notifyAdminAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (
			($userId = $this->request('userId')) &&
			Loader::includeModule('im')
		)
		{
			$admins = $this->getAdmins();
			if (isset($admins[$userId]))
			{
				$params = $this->arParams;
				$groupId = $params['GROUP_ID'];
				\CIMNotify::Add(array(
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => $this->userId,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'tasks',
					'NOTIFY_TAG' => 'TASKS|NOTIFY_ADMIN|'.$userId.'|'.$this->userId,
					'NOTIFY_MESSAGE' => Loc::getMessage('TASK_ACCESS_NOTIFY_MESSAGE', array(
						'#URL#' => $groupId > 0
									? str_replace('#group_id#', $groupId, $params['~PATH_TO_GROUP_TASKS'])
									: $params['~PATH_TO_TASKS']
					))
				));
			}
		}
		return array(
			'status' => 'success'
		);
	}

	/**
	 * Set order for new task (desc, asc).
	 * @return array
	 */
	public function setNewTaskOrderAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$order = $this->request('order');
		$groupId = $this->arParams['GROUP_ID'];

		if (
			$order &&
			in_array($order, ['asc', 'desc', 'actual']) &&
			$this->canSortTasks()
		)
		{
			if ($groupId > 0 && $this->arParams['PERSONAL'] !== 'Y')
			{
				ProjectsTable::set($groupId, array(
					'ORDER_NEW_TASK' => $order
				));
			}
			else
			{
				\CUserOptions::setOption('tasks', static::ORDER_OPTION, $order);
			}
		}

		return array(
			'newTaskOrder' => $this->getNewTaskOrder()
		);
	}

	/**
	 * Delegate task to other.
	 * @return array
	 */
	public function delegateTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (
			($taskId = $this->request('taskId')) &&
			($responsible = $this->request('userId'))
		)
		{
			$groupAction = $this->request('groupAction') === 'Y';
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);

				try
				{
					$taskData = $task->getData();
				}
				catch (TasksException $e)
				{
					$this->addError('TASK_LIST_ERROR_CHANGE_DEADLINE');
					return [];
				}

				if (
					$taskData['CREATED_BY'] == $this->userId
					&& $task->checkAccess(ActionDictionary::ACTION_TASK_EDIT)
				)
				{
					$task->update(array(
						'RESPONSIBLE_ID' => $responsible
					));
				}
				elseif ($task->checkAccess(ActionDictionary::ACTION_TASK_DELEGATE, \Bitrix\Tasks\Access\Model\TaskModel::createFromId((int) $taskId)))
				{
					$task->delegate($responsible);
				}
				elseif ($task->checkAccess(ActionDictionary::ACTION_TASK_EDIT))
				{
					$task->update(array(
						'RESPONSIBLE_ID' => $responsible
					));
				}

				//tmp, bug #85959
				if (false && ($e = $this->application->GetException()))
				{
					$this->addError($e->getString());
				}
				else if (!$groupAction)
				{
					return $this->getRawData($taskId, $this->request('columnId'));
				}
			}
		}

		return array();
	}

	/**
	 * Provides some actions with task members.
	 * @param string $subAction Sub action code.
	 * @return array
	 */
	protected function addMemberTask(string $subAction): array
	{
		if (
			($taskId = $this->request('taskId')) &&
			($userId = $this->request('userId'))
		)
		{
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($subAction === 'addAccomplice')
				{
					if ($task->isActionAllowed(CTaskItem::ACTION_EDIT))
					{
						\CTasks::addAccomplices($taskId, [$userId]);
					}
				}
				else if ($subAction === 'addAuditor')
				{
					\CTasks::addAuditors($taskId, [$userId]);
				}
			}
		}
		return [];
	}

	/**
	 * Provides action to add accomplice for task.
	 * @return array
	 */
	public function addAccompliceTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->addMemberTask('addAccomplice');
	}

	/**
	 * Provides action to add auditor for task.
	 * @return array
	 */
	public function addAuditorTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->addMemberTask('addAuditor');
	}

	/**
	 * Provides action to add to favorite task or to remove from.
	 * @param string $subAction Sub action code.
	 * @return array
	 */
	protected function favoriteTask(string $subAction): array
	{
		if ($taskId = $this->request('taskId'))
		{
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($subAction === 'add')
				{
					$task->addToFavorite();
				}
				else if ($subAction === 'delete')
				{
					$task->deleteFromFavorite();
				}
			}
		}
		return [];
	}

	/**
	 * Provides action to add to favorite task.
	 * @return array
	 */
	public function addFavoriteTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->favoriteTask('add');
	}

	/**
	 * Provides action to delete from favorite task.
	 * @return array
	 */
	public function deleteFavoriteTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->favoriteTask('delete');
	}

	/**
	 * Delegate author's rights of task to others.
	 * @return array
	 */
	public function changeAuthorTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (
			($taskId = $this->request('taskId')) &&
			($author = $this->request('userId'))
		)
		{
			$groupAction = $this->request('groupAction') === 'Y';
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($task->checkAccess(ActionDictionary::ACTION_TASK_EDIT))
				{
					try
					{
						$task->update(array('CREATED_BY' => $author));
					}
					catch (\TasksException $e)
					{
						if (!$groupAction)
						{
							$this->addError($e->getMessageOrigin());
							return array();
						}
					}
				}
				//tmp, bug #85959
				if (false && ($e = $this->application->GetException()))
				{
					$this->addError($e->getString());
				}
				else if (!$groupAction)
				{
					return $this->getRawData($taskId, $this->request('columnId'), false);
				}
			}
		}

		return array();
	}

	/**
	 * Set deadline to the task.
	 * @return array
	 */
	public function deadlineTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (
			($taskId = $this->request('taskId')) &&
			($deadline = $this->request('deadline'))
		)
		{
			$groupAction = $this->request('groupAction') === 'Y';
			$deadlineDT = new DateTime($deadline);
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($task->checkAccess(ActionDictionary::ACTION_TASK_DEADLINE))
				{
					$update = array(
						'DEADLINE' => $deadline
					);
					try
					{
						$fields = $task->getData();
					}
					catch (TasksException $e)
					{
						$this->addError('TASK_LIST_ERROR_CHANGE_DEADLINE');
						return array();
					}

					// if date start great then deadline
					if ($fields['START_DATE_PLAN'] != '')
					{
						$fields['START_DATE_PLAN'] = new DateTime($fields['START_DATE_PLAN']);
						if ($fields['START_DATE_PLAN']->getTimestamp() >= $deadlineDT->getTimestamp())
						{
							$update['START_DATE_PLAN'] = DateTime::createFromTimestamp($deadlineDT->getTimestamp() - $this->timeOffset - 3600);
						}
					}
					// update
					try
					{
						$task->update($update);
					}
					catch (\TasksException $e)
					{
						if (!$groupAction)
						{
							$message = $e->getMessage();
							$data = current(unserialize($message, ['allowed_classes' => false]));
							$this->addError($data['text']);
							return array();
						}
					}
				}
				else if (!$groupAction)
				{
					$this->addError('TASK_LIST_ERROR_CHANGE_DEADLINE');
					return array();
				}

				/*if (($e = $this->application->GetException()))
				{
					$this->addError($e->getString());
					return array();
				}*/

				if (!$groupAction)
				{
					if ($this->arParams['TIMELINE_MODE'] == 'Y')
					{
						$rows = $this->getData(
							['ID' => $taskId]
						);
						if ($rows)
						{
							return array_shift($rows);
						}
					}
					else
					{
						return $this->getRawData($taskId, $this->request('columnId'));
					}
				}
			}
		}

		return array();
	}

	/**
	 * Changes task group.
	 * @return array
	 */
	public function changeGroupTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if (
			($taskId = $this->request('taskId')) &&
			($groupId = $this->request('groupId')) &&
			Loader::includeModule('socialnetwork')
		)
		{
			$userInGroup = \CSocNetUserToGroup::getUserRole(
				$this->userId,
				$groupId
			) !== false;
			if (!$userInGroup)
			{
				return [];
			}
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($task->isActionAllowed(CTaskItem::ACTION_EDIT))
				{
					$task->update([
						'GROUP_ID' => $groupId
					]);
				}
			}
		}

		return [];
	}

	/**
	 * Deletes task.
	 * @return array
	 */
	public function deleteTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		if ($taskId = $this->request('taskId'))
		{
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				$task = CTaskItem::getInstance($taskId, $this->userId);
				if ($task->isActionAllowed(CTaskItem::ACTION_REMOVE))
				{
					$task->delete();
				}
			}
		}

		return [];
	}

	/**
	 * Group similar actions.
	 * @param string $code Code of action.
	 * @return array
	 */
	protected function actionsSimilar(string $code): array
	{
		if (($taskId = $this->request('taskId')))
		{
			$groupAction = $this->request('groupAction') === 'Y';
			$taskIds = (array) $taskId;
			foreach ($taskIds as $taskId)
			{
				switch ($code)
				{
					case 'start':
					case 'pause':
						$task = CTaskItem::getInstance($taskId, $this->userId);
						if ($task->checkAccess($code === 'start' ? ActionDictionary::ACTION_TASK_START : ActionDictionary::ACTION_TASK_PAUSE))
						{
							if ($code === 'start')
							{
								$task->startExecution();
							}
							else
							{
								$task->pauseExecution();
							}
						}
						break;
					case 'complete':
						$this->completeTask($taskId);
						break;
					case 'mute':
						UserOption::add($taskId, $this->userId, UserOption\Option::MUTED);
						break;
					case 'unmute':
						UserOption::delete($taskId, $this->userId, UserOption\Option::MUTED);
						break;
				}
				//tmp, bug #85959
				if (false && ($e = $this->application->GetException()))
				{
					$this->addError($e->getString());
				}
				else if (!$groupAction)
				{
					return $this->getRawData($taskId, $this->request('columnId'));
				}
			}
		}

		return [];
	}

	private function completeTask(int $taskId): void
	{
		try
		{
			$task = CTaskItem::getInstance($taskId, $this->userId);
			if (!$task->checkAccess(ActionDictionary::ACTION_TASK_COMPLETE_RESULT))
			{
				$this->addError('TASKS_KANBAN_RESULT_REQUIRED');
				return;
			}

			if (
				$task->checkAccess(ActionDictionary::ACTION_TASK_COMPLETE)
				|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
			)
			{
				$task->complete();
				$this->onTaskComplete();
			}
		}
		catch(Exception $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

	private function renewTask(int $taskId): void
	{
		try
		{
			$task = CTaskItem::getInstance($taskId, $this->userId);
			if (
				$task->checkAccess(ActionDictionary::ACTION_TASK_RENEW)
				|| $task->checkAccess(ActionDictionary::ACTION_TASK_APPROVE)
			)
			{
				$queryObject = \CTasks::getList(
					[],
					['ID' => $taskId, '=STATUS' => Status::COMPLETED],
					['ID'],
					['USER_ID' => $this->userId]
				);
				if ($queryObject->fetch())
				{
					$task->renew();
				}
			}
		}
		catch(Exception $exception)
		{
			$this->addError($exception->getMessage());
		}
	}

	/**
	 * Start task exection.
	 * @return array
	 */
	public function startTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->actionsSimilar('start');
	}

	/**
	 * Pause task exection.
	 * @return array
	 */
	public function pauseTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->actionsSimilar('pause');
	}

	/**
	 * Mute task.
	 * @return array
	 */
	public function muteTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->actionsSimilar('mute');
	}

	/**
	 * Unmute task.
	 * @return array
	 */
	public function unmuteTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->actionsSimilar('unmute');
	}

	/**
	 * Complete the task.
	 * @return array
	 */
	public function completeTaskAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		return $this->actionsSimilar('complete');
	}

	/**
	 * Converter tasks of My Plan.
	 * @return array
	 */
	public function converterMPAction(): array
	{
		if (!$this->checkBeforeAction())
		{
			return [];
		}

		$params = $this->arParams;

		if ($params['PERSONAL'] === 'Y')
		{
			$lastId = $this->request('last');
			$checkStages = StagesTable::getStages(
				$params['STAGES_ENTITY_ID'],
				true
			);
			[$rows, $res] = $this->getList(array(
				'select' => array(
					'ID'
				),
				'navigate' => array(
					'NAV_PARAMS' => array(
						'nTopCount' => 50
					)
				),
				'filter' => array(
					'MEMBER' => $params['STAGES_ENTITY_ID'],
					'>ID' => $lastId
				),
				'order' => array(
					'ID' => 'ASC'
				)
			), true);
			if (!empty($rows))
			{
				$defaultStageId = StagesTable::getDefaultStageId(
					$params['STAGES_ENTITY_ID']
				);
				// check for each task that it exists in TaskStageTable
				$ids = array();
				$checkTS = array();
				foreach ($rows as $row)
				{
					$ids[] = $row['ID'];
				}
				$resTS = TaskStageTable::getList(array(
					'filter' => array(
						'STAGE_ID' => array_keys($checkStages),
						'TASK_ID' => $ids
					)
				));
				while ($rowTS = $resTS->fetch())
				{
					if (!isset($checkTS[$rowTS['TASK_ID']]))
					{
						$checkTS[$rowTS['TASK_ID']] = array();
					}
					$checkTS[$rowTS['TASK_ID']][] = $rowTS['ID'];
				}
				foreach ($rows as $row)
				{
					// if double exists, remove all excepts last
					if (is_array($checkTS[$row['ID']] ?? null) && count($checkTS[$row['ID']] ?? []) > 1)
					{
						array_pop($checkTS[$row['ID']]);
						foreach ($checkTS[$row['ID']] as $tsId)
						{
							TaskStageTable::delete($tsId);
						}
					}
					// not exists in any stage - add in default
					elseif (!isset($checkTS[$row['ID']]))
					{
						TaskStageTable::add(array(
							'STAGE_ID' => $defaultStageId,
							'TASK_ID' => $row['ID']
						));
					}
					$lastId = $row['ID'];
				}
				$finish = false;
			}
			else
			{
				$finish = true;
			}
			// flag that all right
			if ($finish)
			{
				\CUserOptions::setOption(
					'tasks',
					'personal_kanban_version',
					StagesTable::MY_PLAN_VERSION,
					false,
					$params['STAGES_ENTITY_ID']
				);
			}

			return array(
				'processed' => count($rows),
				'last' => $lastId,
				'finish' => $finish
			);
		}

		return [];
	}

	/**
	 * Save user selected fields for tasks view in kanban.
	 */
	public function saveUserSelectedFieldsAction(): bool
	{
		if (!$this->checkBeforeAction())
		{
			return false;
		}

		$fields = $this->request('fields');
		if (empty($fields))
		{
			return false;
		}

		return $this->kanbanUserSettings->saveUserSelectedFields($fields);
	}

	private function isScrum(): bool
	{
		if (isset($this->arParams['IS_SCRUM']) && $this->arParams['IS_SCRUM'] === true)
		{
			return true;
		}
		return false;
	}

	private function getViewMode(array $params): string
	{
		$mode = 'kanban';
		if ($this->isScrum())
		{
			$mode .= '_scrum';
		}
		if (isset($params['TIMELINE_MODE']) && $params['TIMELINE_MODE'] === 'Y')
		{
			$mode .= '_timeline';
		}
		if (isset($params['PERSONAL']) && $params['PERSONAL'] === 'Y')
		{
			$mode .= '_personal';
		}
		return $mode;
	}

	/**
	 * The method removes extra task ids from pull that should not be in the current kanban grid.
	 *
	 * @param array $inputTaskIds Input task ids.
	 * @param int $parentId Non-zero if this is a grid of subtasks.
	 * @param int $groupId Group id.
	 * @param int $userId User id.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function sortTaskIdsByScrumFilter(
		array $inputTaskIds,
		int $parentId,
		int $groupId,
		int $userId
	): array
	{
		$isChildScrumGrid = $parentId !== 0;

		$taskService = new TaskService($userId);

		if ($isChildScrumGrid)
		{
			$subTaskIds = $taskService->getSubTaskIds($groupId, $parentId, false);

			return array_intersect($inputTaskIds, $subTaskIds);
		}
		else
		{
			$itemService = new ItemService();
			$sprintService = new SprintService();

			$sprint = $sprintService->getActiveSprintByGroupId($groupId);
			if ($sprint->isEmpty())
			{
				return [];
			}

			$taskIds = [];

			$scrumManager = new ScrumManager($groupId);

			$mapOfExistenceOfSubtasks = $scrumManager->buildMapOfExistenceOfSubtasks(
				$sprint->getId(),
				$itemService->getTaskIdsByEntityId($sprint->getId())
			);

			$queryObject = TaskTable::getList([
				'filter' => [
					'@ID' => $inputTaskIds,
					'GROUP_ID' => $groupId,
				],
				'select' => ['ID', 'PARENT_ID'],
			]);
			while ($data = $queryObject->fetch())
			{
				$isChildTask = ((int) $data['PARENT_ID']) > 0;
				if ($isChildTask)
				{
					$hasParentTaskInCurrentSprint = !empty($mapOfExistenceOfSubtasks[$data['PARENT_ID']]);
					if (!$hasParentTaskInCurrentSprint)
					{
						$taskIds[] = (int) $data['ID'];
					}
				}
				else
				{
					$isParentTaskInCurrentSprint = !empty($mapOfExistenceOfSubtasks[$data['ID']]);
					if ($isParentTaskInCurrentSprint)
					{
						$scrumItem = current($itemService->getItemsBySourceIds([$data['ID']]));
						$isParentTaskInProcess = (
							$scrumItem
							&& !$scrumItem->getInfo()->isVisibilitySubtasks()
						);
						if ($isParentTaskInProcess)
						{
							$taskIds[] = (int) $data['ID'];
						}
					}
					else
					{
						$taskIds[] = (int) $data['ID'];
					}
				}
			}

			return $taskIds;
		}
	}

	private function onTaskComplete(): void
	{
		if ($this->isScrum())
		{
			Analytics::getInstance($this->userId)->onTaskComplete(
				Analytics::SECTION['scrum'],
				Analytics::SUB_SECTION['kanban'],
				Analytics::ELEMENT['complete_button'],
			);
		}
	}

	private function getStageIdsWithTasks(array $stages): array
	{
		$stagesWithTasks = array_filter($stages, static fn (array $column): bool => (int)$column['total'] > 0 || $column['type'] === StagesTable::SYS_TYPE_NEW);
		$stagesWithTasks = array_column($stagesWithTasks, 'id');

		Collection::normalizeArrayValuesByInt($stagesWithTasks, false);

		return $stagesWithTasks;
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getErrorByCode($code): void
	{
		return;
	}

	public function getErrors()
	{
		return $this->errors;
	}
}
