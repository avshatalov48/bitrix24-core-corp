<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Entity;
use \Bitrix\Main\Error;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

use \Bitrix\Tasks\Kanban\StagesTable;
use \Bitrix\Tasks\Kanban\TaskStageTable;
use \Bitrix\Tasks\ProjectsTable;
use \Bitrix\Tasks\Helper\Filter;
use \Bitrix\Tasks\Internals\Task;
use \Bitrix\Tasks\Integration\Disk\Connector\Task as ConnectorTask;

class TasksKanbanComponent extends \CBitrixComponent
{
	const TASK_TYPE_USER = 'user';
	const TASK_TYPE_GROUP = 'group';

	const USER_DEPARTMENT_CODE = 'UF_DEPARTMENT';
	const USER_WEBDAV_CODE = 'UF_TASK_WEBDAV_FILES';
	const USER_CRM_CODE = 'UF_USER_CRM_ENTITY';

	const USER_TYPE_MAIL = 'email';

	const DEF_PAGE_SIZE = 20;

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
			$this->addError('TASK_LIST_ACCESS_DENIED');
			$init = false;
		}

		// init vars or exit on fatal
		if (!$init)
		{
			return $init;
		}
		else
		{
			$this->initVars();
		}

		// get data of user or group
		if ($this->taskType == static::TASK_TYPE_USER || $params['GROUP_ID_FORCED'])
		{
			$result['USER'] = \CUser::GetByID($params['USER_ID'])->fetch();
			if (!$result['USER'])
			{
				$this->addError('TASK_LIST_USER_NOT_FOUND');
				$init = false;
			}
		}
		elseif ($this->taskType == static::TASK_TYPE_GROUP)
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
				$this->addError('TASK_LIST_ACCESS_TO_GROUP_DENIED');
				$init = false;
			}
		}

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
		$allowFromRequest = array('USER_ID', 'GROUP_ID', 'GROUP_ID_FORCED', 'PERSONAL');
		foreach ($allowFromRequest as $code)
		{
			if (isset($request[$code]))
			{
				$params[$code] = $request[$code];
			}
		}

		// vars
		$params['SONET_LOAD'] = $sonetOn = Loader::includeModule('socialnetwork');
		$params['GROUP_ID_FORCED'] = isset($params['GROUP_ID_FORCED']) ? $params['GROUP_ID_FORCED'] : false;
		$params['IS_AJAX'] = isset($params['IS_AJAX']) && $params['IS_AJAX'] == 'Y' ? 'Y' : 'N';
		$params['SET_TITLE'] = isset($params['SET_TITLE']) && $params['SET_TITLE'] == 'Y' ? 'Y' : 'N';
		$params['PERSONAL'] = isset($params['PERSONAL']) && $params['PERSONAL'] == 'Y' ? 'Y' : 'N';
		$params['USER_ID'] = !isset($params['USER_ID']) || intval($params['USER_ID']) <= 0 ? $this->userId : intval($params['USER_ID']);
		$params['GROUP_ID'] = !isset($params['GROUP_ID']) || !$sonetOn ? 0 : max(0, intval($params['GROUP_ID']));
		$params['~NAME_TEMPLATE'] = !isset($params['~NAME_TEMPLATE'])
									? \CSite::GetNameFormat(false)
									: str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), trim($params['~NAME_TEMPLATE']));

		// force set last user group
		if ($params['PERSONAL'] == 'Y')
		{
			if ($params['GROUP_ID'] > 0)
			{
				$params['GROUP_ID_FORCED'] = true;
			}
			$this->arParams['STAGES_ENTITY_ID'] = $params['USER_ID'];
			StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
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

		$this->filterInstance = Filter::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);

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
	}

	/**
	 * Check access to group tasks for current user.
	 * @param int $groupId Id of group.
	 * @return boolean
	 */
	protected function canReadGroupTasks($groupId)
	{
		static $access = array();

		if (array_key_exists($groupId, $access))
		{
			return $access[$groupId];
		}

		$activeFeatures = \CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $groupId);
		if (!is_array($activeFeatures) || !array_key_exists('tasks', $activeFeatures))
		{
			$access[$groupId] = false;
			return $access[$groupId];
		}

		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'view_all');
		$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		if (!$bCanViewGroup)
		{
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($groupId), 'tasks', 'view');
			$bCanViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
		}

		$access[$groupId] = $bCanViewGroup;

		return $access[$groupId];
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

		if ($this->arParams['PERSONAL'] == 'Y')
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
					($by = 'timestamp_x'),
					($order = 'desc'),
					array(
						'ID' => implode(' | ', $users),
						'ACTIVE' => 'Y'
					)
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

		// get last viewed group
		$res = \Bitrix\Socialnetwork\WorkgroupViewTable::getList(array(
			'select' => array(
				'GROUP_ID'
			),
			'filter' => array(
				'USER_ID' => $this->userId,
				'=GROUP.ACTIVE' => 'Y',
				'=GROUP.CLOSED' => 'N'
			),
			'order' => array(
				'DATE_VIEW' => 'DESC'
			)
		));
		while ($row = $res->fetch())
		{
			if ($this->canReadGroupTasks($row['GROUP_ID']))
			{
				$lastGroupId = $row['GROUP_ID'];
				break;
			}
		}
		if (!$lastGroupId)
		{
			// get by date activity
			$res = \CSocNetUserToGroup::GetList(
				array(
					'GROUP_DATE_ACTIVITY' => 'DESC'
				),
				array(
					'USER_ID' => $this->userId,
					'!ROLE' => array(
						SONET_ROLES_BAN,
						SONET_ROLES_REQUEST
					),
					'USER_ACTIVE' => 'Y',
					'GROUP_ACTIVE' => 'Y'
				),
				false, false,
				array(
					'GROUP_ID'
				)
			);
			while ($row = $res->fetch())
			{
				if ($this->canReadGroupTasks($row['GROUP_ID']))
				{
					$lastGroupId = $row['GROUP_ID'];
					break;
				}
			}
		}

		return $lastGroupId;
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
	protected function getErrors($string = true)
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
		else
		{
			return $this->errors;
		}
	}

	/**
	 * Add one more error.
	 * @param string $code Code of error (lang code).
	 * @return void
	 */
	protected function addError($code)
	{
		$message = Loc::getMessage($code);
		$this->errors[] = new Error($message != '' ? $message : $code, $code);
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
			array(
				'TITLE', 'DEADLINE', 'ALLOW_CHANGE_DEADLINE', 'CREATED_DATE', 'STAGE_ID',
				'COMMENTS_COUNT', 'TIME_SPENT_IN_LOGS', 'ALLOW_TIME_TRACKING', 'REAL_STATUS',
				'CREATED_BY', 'RESPONSIBLE_ID', 'AUDITORS', 'ACCOMPLICES', 'TIME_ESTIMATE',
				'PRIORITY', 'DATE_START', 'SORTING'
			)
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
	protected function getFilter()
	{
		static $filling = false;

		if ($filling)
		{
			return $this->filter;
		}
		else
		{
			$filling = true;
		}

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
		if (!array_key_exists('ZOMBIE', $filter))
		{
			$filter['ZOMBIE'] = 'N';
		}
		if ($params['PERSONAL'] != 'Y' || $params['GROUP_ID'] > 0)
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
	 * Set page id / page number.
	 * @param int $id Page id.
	 * @return void
	 */
	protected function setPageId($id)
	{
		$this->arParams['ITEMS_PAGE'] = $id;
	}

	/**
	 * Get order array.
	 * @return array
	 */
	protected function getOrder()
	{
		// by default
		$this->order = array(
			'SORTING' => 'ASC',
			'STATUS_COMPLETE' => 'ASC',
			'DEADLINE' => 'ASC,NULLS',
			'ID' => 'ASC'
		);

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
			$this->arParams['PERSONAL'] != 'Y' &&
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
			$this->arParams['PERSONAL'] == 'Y' &&
			isset($params['filter']['STAGE_ID'])
		)
		{
			$params['filter']['STAGES_ID'] = $params['filter']['STAGE_ID'];
			unset($params['filter']['STAGE_ID']);
		}
		list($rows, $res) = \CTaskItem::fetchList(
			$this->userId,
			isset($params['order']) ? $params['order'] : array(),
			isset($params['filter']) ? $params['filter'] : array(),
			isset($params['navigate']) ? $params['navigate'] : array(),
			isset($params['select']) ? $params['select'] : array()
		);

		return $asIs ? array($rows, $res) : $rows;
	}

	/**
	 * Fill data-array with files count and background.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getFiles(array $items)
	{
		if (empty($items))
		{
			return $items;
		}

		if (Loader::includeModule('disk'))
		{
			// get counts
			$cnt = ConnectorTask::getFilesCount(array_keys($items));
			foreach ($cnt as $taskId => $c)
			{
				$items[$taskId]['data']['count_files'] = $c;
			}
			// get covers
			$covers = ConnectorTask::getCover(
				array_keys($items),
				$this->previewSize['width'],
				$this->previewSize['height']
			);
			foreach ($covers as $taskId => $cover)
			{
				$items[$taskId]['data']['background'] = $cover;
			}
		}

		return $items;
	}

	/**
	 * Fill data-array with checklist counts.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getCheckList(array $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$res = Task\CheckListTable::getList(array(
			'select' => array(
				'TASK_ID', 'IS_COMPLETE',
				new Entity\ExpressionField('CNT', 'COUNT(TASK_ID)')
			),
			'filter' => array(
				'TASK_ID' => array_keys($items),
				'!=TITLE' => '==='
			),
			'group' => array(
				'TASK_ID', 'IS_COMPLETE'
			)
		));
		while ($row = $res->fetch())
		{
			$checkList =& $items[$row['TASK_ID']]['data']['check_list'];
			$checkList[$row['IS_COMPLETE'] == 'Y' ? 'complete' : 'work'] = $row['CNT'];
		}

		return $items;
	}

	/**
	 * Fill data-array with tags.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getTags(array $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$res = Task\TagTable::getList(array(
			'select' => array(
				'TASK_ID', 'NAME'
			),
			'filter' => array(
				'TASK_ID' => array_keys($items)
			)
		));
		while ($row = $res->fetch())
		{
			$tags =& $items[$row['TASK_ID']]['data']['tags'];
			$tags[] = $row['NAME'];
		}

		return $items;
	}

	/**
	 * Fill data-array with time starting delta.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getTimeStarted(array $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$res = \Bitrix\Tasks\Internals\Task\TimerTable::getList(array(
			'filter' => array(
				'TASK_ID' => array_keys($items),
				'>TIMER_STARTED_AT' => 0
			)
		));
		while ($row = $res->fetch())
		{
			$delta = time() - $row['TIMER_STARTED_AT'];
			$items[$row['TASK_ID']]['data']['time_logs'] += $delta;
			//$items[$row['TASK_ID']]['data']['time_logs_start'] += $delta;
		}

		return $items;
	}

	/**
	 * Fill data-array with new log-data.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getNewLog(array $items)
	{
		if (empty($items))
		{
			return $items;
		}
		// first get last viewed dates
		$res = Task\ViewedTable::getList(array(
			'filter' => array(
				'USER_ID' => $this->userId,
				'TASK_ID' => array_keys($items)
			)
		));
		while ($row = $res->fetch())
		{
			$items[$row['TASK_ID']]['data']['date_view'] = $row['VIEWED_DATE'];
		}
		// then get new log after view
		$filterLog = array(
			'LOGIC' => 'OR'
		);
		foreach ($items as $id => &$item)
		{
			if ($item['data']['date_view'])
			{
				$filterLog[] = array(
					'>CREATED_DATE' => $item['data']['date_view'],
					'TASK_ID' => $id
				);
			}
			$item['data']['date_view'] = $item['data']['date_view']->getTimestamp();
		}
		unset($item);
		$res = Task\LogTable::getList(array(
			'select' => array(
				'TASK_ID', 'FIELD', 'FROM_VALUE', 'TO_VALUE'
			),
			'filter' => array(
				'!USER_ID' => $this->userId,
				'FIELD' => array(
					'COMMENT', static::USER_WEBDAV_CODE,
					'CHECKLIST_ITEM_CREATE'
				),
				$filterLog
			)
		));
		while ($row = $res->fetch())
		{
			$log =& $items[$row['TASK_ID']]['data']['log'];

			// wee need only files and comments
			if ($row['FIELD'] == 'COMMENT')
			{
				$log['comment']++;
			}
			elseif ($row['FIELD'] == static::USER_WEBDAV_CODE)
			{
				$row['FROM_VALUE'] = $row['FROM_VALUE'] == '' ? 0 : count(explode(',', $row['FROM_VALUE']));
				$row['TO_VALUE'] = $row['TO_VALUE'] == '' ? 0 : count(explode(',', $row['TO_VALUE']));
				if ($row['TO_VALUE'] > $row['FROM_VALUE'])
				{
					$log['file'] += ($row['TO_VALUE'] - $row['FROM_VALUE']);
				}
			}
			elseif ($row['FIELD'] == 'CHECKLIST_ITEM_CREATE')
			{
				$log['checklist']++;
			}
		}

		return $items;
	}

	/**
	 * Get info about users.
	 * @param array $items Task items.
	 * @return array
	 */
	protected function getUsers(array $items)
	{
		// get users
		$members = array();
		foreach ($items as $item)
		{
			if ($item['data']['author'] > 0)
			{
				$members[] = $item['data']['author'];
			}
			if ($item['data']['responsible'] > 0)
			{
				$members[] = $item['data']['responsible'];
			}
		}
		// set users
		if (!empty($members))
		{
			$users = array();
			$select = array(
				'ID', 'PERSONAL_PHOTO', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EXTERNAL_AUTH_ID',
				static::USER_DEPARTMENT_CODE
			);
			if (Loader::includeModule('crm'))
			{
				$select[] = static::USER_CRM_CODE;
			}
			$res = \Bitrix\Main\UserTable::getList(array(
				'select' => $select,
				'filter' => array(
					'ID' => $members
				)
			));
			while ($row = $res->fetch())
			{
				if ($row['PERSONAL_PHOTO'])
				{
					$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
						$row['PERSONAL_PHOTO'],
						$this->avatarSize,
						BX_RESIZE_IMAGE_EXACT
					);
				}
				$row['USER_NAME'] = \CUser::FormatName(
					$this->arParams['~NAME_TEMPLATE'],
					$row, true, false);
				$users[$row['ID']] = array(
					'id' => $row['ID'],
					'photo' => $row['PERSONAL_PHOTO'],
					'name' => $row['USER_NAME'],
					'crm' => false,
					'mail' => false,
					'extranet' => false
				);
				if (
					isset($row[static::USER_CRM_CODE]) &&
					$row[static::USER_CRM_CODE]
				)
				{
					$users[$row['ID']]['crm'] = true;
				}
				elseif ($row['EXTERNAL_AUTH_ID'] == static::USER_TYPE_MAIL)
				{
					$users[$row['ID']]['mail'] = true;
				}
				elseif (
					!isset($row[static::USER_DEPARTMENT_CODE][0]) ||
					!$row[static::USER_DEPARTMENT_CODE][0]
				)
				{
					$users[$row['ID']]['extranet'] = true;
				}
			}
		}
		// fill users
		if (!empty($members))
		{
			foreach ($items as &$item)
			{
				$item['data']['author'] = isset($users[$item['data']['author']]) ? $users[$item['data']['author']] : null;
				$item['data']['responsible'] = isset($users[$item['data']['responsible']]) ? $users[$item['data']['responsible']] : null;
			}
			unset($item);
		}

		return $items;
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
	protected function getColumns($assoc = false)
	{
		$columns = array();
		$counts = array();
		$canSort = $this->canSortTasks();

		// get counts
		$res = StagesTable::getStagesCount(
			$this->getFilter(),
			$this->arParams['USER_ID'] ? $this->arParams['USER_ID'] : false
		);
		while ($row = $res->fetch())
		{
			$counts[$row['STAGE_ID']] = $row['CNT'];
		}

		// get columns
		foreach (StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']) as $stage)
		{
			$count = 0;
			$stages = (array)StagesTable::getStageIdByCode(
				$stage['ID'],
				$this->arParams['STAGES_ENTITY_ID']
			);
			foreach ($stages as $stId)
			{
				$count += $counts[$stId];
			}

			$columns[$stage['ID']] = array(
				'id' => $stage['ID'],
				'name' => $stage['TITLE'],
				'color' => $stage['COLOR'],
				'type' => $stage['SYSTEM_TYPE'],
				'sort' => $stage['SORT'],
				'total' => $count,
				'canSort' => $this->isAdmin()
			);
		}

		$columns = $this->sendEvent('KanbanComponentGetColumns', $columns);

		return $assoc ? $columns : array_values($columns);
	}

	/**
	 * Fill one item with task data.
	 * @param object $task Task item.
	 * @return array
	 */
	protected function fillData(\CTaskItem $task)
	{
		static $endDayTime = null;

		if ($endDayTime === null)
		{
			$endDayTime = Option::get('calendar', 'work_time_end', 19);
		}

		$item = $task->getData();
		$canEdit = $task->isActionAllowed(\CTaskItem::ACTION_EDIT);
		$data = array(
			// base
			'id' => $item['ID'],
			'stage_id' => $item['STAGE_ID'],
			'name' => $item['TITLE'],
			'background' => '',
			'author' => $item['CREATED_BY'],
			'responsible' => $item['RESPONSIBLE_ID'],
			'tags' => array(),
			// time
			'time_tracking' => $item['ALLOW_TIME_TRACKING'] == 'Y',
			'time_logs' => (int)$item['TIME_SPENT_IN_LOGS'],
			'time_logs_start' => (int)$item['TIME_SPENT_IN_LOGS'],
			'time_estimate' => $item['TIME_ESTIMATE'],
			// rights
			'allow_change_deadline' => $task->isActionAllowed(\CTaskItem::ACTION_CHANGE_DEADLINE),
			'allow_delegate' => $task->isActionAllowed(\CTaskItem::ACTION_DELEGATE) || $canEdit,
			'allow_complete' => $task->isActionAllowed(\CTaskItem::ACTION_COMPLETE),
			'allow_start' => $task->isActionAllowed(\CTaskItem::ACTION_START) || $task->isActionAllowed(\CTaskItem::ACTION_PAUSE),
			'allow_time_tracking' => $task->isActionAllowed(\CTaskItem::ACTION_START_TIME_TRACKING),
			'allow_edit' => $canEdit,
			// dates
			'date_deadline' => $item['DEADLINE'] != '' ? new DateTime($item['DEADLINE']) : '',
			'date_start' => $item['DATE_START'] != '' ? new DateTime($item['DATE_START']) : '',
			'date_view' => new DateTime($item['CREATED_DATE']),
			'date_day_end' => mktime($endDayTime - $this->timeOffset/3600, 0, 0),
			// counts
			'count_comments' => (int)$item['COMMENTS_COUNT'],
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
			'high' => $item['PRIORITY'] == \CTasks::PRIORITY_HIGH,
			'new' => $item['STATUS'] == \CTasks::METASTATE_VIRGIN_NEW,
			'in_progress' => $item['REAL_STATUS'] == \CTasks::STATE_IN_PROGRESS,
			'deferred' => $item['STATUS'] == \CTasks::STATE_DEFERRED,
			'completed' => $item['STATUS'] == \CTasks::STATE_COMPLETED,
			'completed_supposedly' => $item['STATUS'] == \CTasks::STATE_SUPPOSEDLY_COMPLETED
		);
		// get timestamps
		if ($data['date_deadline'])
		{
			$data['date_deadline'] = $data['date_deadline']->getTimestamp()/* - $this->timeOffset*/;
			$data['overdue'] = $data['date_deadline'] <= time() + $this->timeOffset;
			$data['overdue_until'] = max(0, $data['date_deadline']);
		}
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
						'columnId' => $task['stage_id'] > 0 ? $task['stage_id'] : $columnId,
						'data' => $task
					)
				);

				// get other data
				$task = $this->getUsers($task);
				$task = $this->getNewLog($task);
				$task = $this->getFiles($task);
				$task = $this->getCheckList($task);
				$task = $this->getTags($task);

				$task = $this->sendEvent('TimelineComponentGetItems', $task);

				return array_pop($task);
			}
		}

		return array();
	}

	/**
	 * Base method for getting data.
	 * @return array
	 */
	protected function getData()
	{
		$items = array();
		$order = $this->getOrder();
		$filter = $this->getFilter();
		$listParams = $this->getListParams();
		$select = $this->getSelect();

		if (isset($filter['ONLY_STAGE_ID']))
		{
			$onlyStageId = $filter['ONLY_STAGE_ID'];
			unset($filter['ONLY_STAGE_ID']);
		}

		$listParams['MAKE_ACCESS_FILTER'] = true;

		// get tasks by stages
		foreach (StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']) as $column)
		{
			$stageId = StagesTable::getStageIdByCode(
				$column['ID'],
				$this->arParams['STAGES_ENTITY_ID']
			);
			if (
				isset($onlyStageId) &&
				!in_array($onlyStageId, (array)$stageId)
			)
			{
				continue;
			}
			$filterTmp = array_merge(
				$filter,
				array(
					'STAGE_ID' => $stageId
				)
			);

			list($rows, $res) = $this->getList(array(
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
				$items[$item['id']] = array(
					'id' => $item['id'],
					'columnId' => $column['ID'],
					'data' => $item
				);
				if (
					isset($filterTmp['ID']) &&
					$filterTmp['ID'] == $item['ID']
				)
				{
					break 2;
				}
			}
		}

		// get other data
		$items = $this->getUsers($items);
		$items = $this->getNewLog($items);
		$items = $this->getFiles($items);
		$items = $this->getCheckList($items);
		$items = $this->getTags($items);
		$items = $this->getTimeStarted($items);

		$items = $this->sendEvent('KanbanComponentGetItems', $items);

		return array_values($items);
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

		if ($params['SET_TITLE'] == 'Y')
		{
			if ($this->itsMyTasks())
			{
				$this->application->setTitle(Loc::getMessage('TASK_KANBAN'.($params['PERSONAL']!='N'?'_PERSONAL':'').'_TITLE'));
			}
			elseif ($this->taskType == static::TASK_TYPE_GROUP && !$params['GROUP_ID_FORCED'])
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
	 * Convert charset from utf-8 to site.
	 * @param mixed $data
	 * @param bool $fromUtf Direction - from (true) or to (false).
	 * @return mixed
	 */
	protected function convertUtf($data, $fromUtf)
	{
		if (SITE_CHARSET != 'UTF-8')
		{
			$from = $fromUtf ? 'UTF-8' : SITE_CHARSET;
			$to = !$fromUtf ? 'UTF-8' : SITE_CHARSET;
			if (is_array($data))
			{
				$data = $this->application->ConvertCharsetArray($data, $from, $to);
			}
			else
			{
				$data = $this->application->ConvertCharset($data, $from, $to);
			}
		}
		return $data;
	}

	/**
	 * Check views for current Kanban. Ask admin for create default.
	 * @return boolean
	 */
	protected function checkViews()
	{
		$params = $this->arParams;
		$this->arResult['MP_CONVERTER'] = 0;

		// for personal - create defaults and copy tasks to default stage
		if ($params['PERSONAL'] == 'Y')
		{
			$checkStages = StagesTable::getStages($params['STAGES_ENTITY_ID'], true);
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
				if ($params['IS_AJAX'] == 'Y')
				{
					return false;
				}
				else
				{
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
									'ID', 'GROUP_NAME', 'GROUP_ID'
								)
							);
							while ($row = $res->fetch())
							{
								if ($this->canReadGroupTasks($row['GROUP_ID']))
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
				return $row['ORDER_NEW_TASK'] ? $row['ORDER_NEW_TASK'] : 'desc';
			}
			else
			{
				return 'desc';
			}
		}
		else
		{
			return \CUserOptions::getOption('tasks', 'order_new_task', 'desc');
		}
	}

	/**
	 * Init arResult.
	 * @return void
	 */
	protected function initResult()
	{
		$this->arResult['ERRORS'] = $this->getErrors();
		$this->arResult['ERRORS_COLLECTION'] = $this->getErrors(false);
		$this->arResult['TASK_TYPE'] = $this->taskType;
		$this->arResult['PAGE'] = $this->getURI();
		$this->arResult['CURRENT_USER_ID'] = $this->userId;
		$this->arResult['ACCESS_CONFIG_PERMS'] = $this->isAdmin();
		$this->arResult['ACCESS_CREATE_PERMS'] = $this->canCreateTasks();
		$this->arResult['ACCESS_SORT_PERMS'] = $this->canSortTasks();
		$this->arResult['NEW_TASKS_ORDER'] = $this->getNewTaskOrder();
		$this->arResult['ITS_MY_TASKS'] = $this->itsMyTasks();
		$this->arResult['ADMINS'] = array();

		$this->arResult['DEFAULT_PRESET_KEY'] = $this->filterInstance->getDefaultPresetKey();

		if (!$this->arResult['VIEWS'])
		{
			$this->arResult['VIEWS'] = array();
		}

		if (!$this->arResult['ACCESS_CONFIG_PERMS'])
		{
			$this->arResult['ADMINS'] = $this->getAdmins();
		}
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		$init = $this->init();

		// if ajax, go to the actions
		if ($this->arParams['IS_AJAX'] == 'Y')
		{
			$this->executeComponentAjax();
			return;
		}

		// if all right, get data
		if ($this->checkViews() && $init)
		{
			$this->arResult['DATA'] = array(
				'columns' => $this->getColumns(),
				'items' => $this->getData(),
				'demo' => false
			);
		}

		// if need converter, check current task count
		if ($this->arResult['MP_CONVERTER'] > 0)
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
		$this->arResult['MANDATORY_EXISTS'] = $this->mandatoryExists();

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
					$return = $this->{'action' . $action}();
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
			if ($errors = $this->getErrors())
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

	/**
	 * Add new task.
	 * @return array
	 */
	protected function actionAddTask()
	{
		if (
			($columnId = $this->request('columnId')) &&
			($taskName = $this->request('taskName'))
		)
		{
			if ($this->mandatoryExists())
			{
				$this->addError('TASK_LIST_TASK_MANDATORY_EXISTS');
				return array();
			}

			if (!$this->canCreateTasks())
			{
				$this->addError('TASK_LIST_TASK_CREATE_DENIED');
				return array();
			}

			$params = $this->arParams;
			$stages = StagesTable::getStages($params['STAGES_ENTITY_ID']);
			$fields = array(
				'TITLE' => $this->convertUtf($taskName, true),
				'CREATED_BY' => $this->userId,
				'RESPONSIBLE_ID' => $params['USER_ID'],
				'GROUP_ID' => $params['GROUP_ID'],
				'STAGE_ID' => isset($stages[$columnId]) ? $columnId : 0
			);
			if ($params['PERSONAL'] == 'Y')
			{
				unset($fields['STAGE_ID']);
			}
			// disable sort / link - its set below here
			if ($params['GROUP_ID'] == 0)
			{
				StagesTable::disablePinForUser($this->userId);
			}
			if ($params['PERSONAL'] == 'Y')
			{
				StagesTable::disableLinkForUser($params['USER_ID']);
			}
			$task = \CTaskItem::add($fields, $this->userId, ['DISABLE_BIZPROC_RUN' => true]);
			if ($task->getId() > 0)
			{
				$newId = $task->getId();
				// set link
				if ($params['PERSONAL'] == 'Y')
				{
					TaskStageTable::add(array(
						'TASK_ID' => $newId,
						'STAGE_ID' => $columnId
					));
				}
				// set sort
				if ($params['GROUP_ID'] == 0)
				{
					$this->setSorting(
						$newId,
						$this->request('beforeItemId'),
						$this->request('afterItemId')
					);
				}
				\Bitrix\Tasks\Integration\Bizproc\Listener::onTaskAdd($newId, $task->getData());
				// output
				return $this->getRawData($newId, $columnId);
			}
			else
			{
				if (($e = $this->application->GetException()))
				{
					$this->addError($e->getString());
				}
			}
		}

		return array();
	}

	/**
	 * Move item from one stage to another.
	 * @return array
	 */
	protected function actionMoveTask()
	{
		if (
			($taskId = $this->request('itemId')) &&
			($columnId = $this->request('columnId')) &&
			$this->canSortTasks()
		)
		{
			$stages = StagesTable::getStages($this->arParams['STAGES_ENTITY_ID']);

			if (isset($stages[$columnId]))
			{
				$task = new \CTasks;
				$filter = array();
				$filter['ID'] = $taskId;
				$rows = $this->getList(array(
					'select' => array('ID'),
					'filter' => $filter
				));
				foreach ($rows as $row)
				{
					$data = $row->getData();
					// update personal stage
					if ($this->arParams['PERSONAL'] == 'Y')
					{
						$resStg = TaskStageTable::getList(array(
							'filter' => array(
								'TASK_ID' => $data['ID'],
								'=STAGE.ENTITY_TYPE' => StagesTable::WORK_MODE_USER,
								'STAGE.ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
							)
						));
						while ($rowStg = $resStg->fetch())
						{
							TaskStageTable::update($rowStg['ID'], array(
								'STAGE_ID' => $columnId
							));

							if ($columnId !== $rowStg['STAGE_ID'])
							{
								\Bitrix\Tasks\Integration\Bizproc\Listener::onPlanTaskStageUpdate(
									$this->arParams['STAGES_ENTITY_ID'],
									$rowStg['TASK_ID'],
									$columnId
								);
							}

						}
					}
					// or common
					else
					{
						$task->update($data['ID'], array(
							'STAGE_ID' => $columnId
						));
					}
					$this->setSorting(
						$data['ID'],
						$this->request('beforeItemId'),
						$this->request('afterItemId')
					);
				}
			}
		}

		return array();
	}

	/**
	 * Get new task info.
	 * @return array
	 */
	protected function actionNewTask()
	{
		if (($taskId = $this->request('taskId')))
		{
			$this->addFilter('ID', $taskId);
			return array_pop($this->getData());
		}

		return array();
	}

	/**
	 * Just refresh task info.
	 * @return array
	 */
	protected function actionRefreshTask()
	{
		if (($taskId = $this->request('taskId')))
		{
			return $this->getRawData(
				$taskId,
				$this->request('columnId')
			);
		}

		return array();
	}

	/**
	 * Get items from one column.
	 * @return array
	 */
	protected function actionGetColumnItems()
	{
		if (($columnId = $this->request('columnId')))
		{
			$this->addFilter('ONLY_STAGE_ID', $columnId);
			if (($pageId = $this->request('pageId')))
			{
				$this->setPageId($pageId);
			}
			return $this->getData();
		}
		return array();
	}

	/**
	 * Modify stage in Kanban.
	 * @return array
	 */
	protected function actionModifyColumn()
	{
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
					isset($fields['id']) &&
					isset($fields['delete']) &&
					$fields['delete']
				)
				{
					if ($this->arParams['PERSONAL'] == 'Y')
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
				else
				{
					if (isset($fields['columnName']) && trim($fields['columnName']) != '')
					{
						$fields = array(
							'ID' => isset($fields['id']) ? (int)$fields['id'] : 0,
							'COLOR' => isset($fields['columnColor']) ? $fields['columnColor'] : '',
							'TITLE' => $this->convertUtf($fields['columnName'], true),
							'AFTER_ID' => isset($fields['afterColumnId']) ? $fields['afterColumnId'] : null,
							'ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
						);
						if ($fields['AFTER_ID'] === null)
						{
							unset($fields['AFTER_ID']);
						}
						$res = StagesTable::updateByCode($fields['ID'], $fields);
						if ($fields['ID'] == 0 && $res->isSuccess())
						{
							$columns = $this->getColumns(true);
							return $columns[$res->getId()];
						}
					}
					else
					{
						$this->addError('TASK_LIST_COLUMN_TITLE_EMPTY');
					}
				}
			}
			else
			{
				$this->addError('TASK_LIST_TASK_ACTION_DENIED');
			}
		}
		return array();
	}

	/**
	 * Move column.
	 * @return array
	 */
	protected function actionMoveColumn()
	{
		if ($this->isAdmin() && ($columnId = $this->request('columnId')))
		{
			$afterColumnId = $this->request('afterColumnId');
			StagesTable::updateByCode($columnId, array(
				'AFTER_ID' => $afterColumnId,
				'ENTITY_ID' => $this->arParams['STAGES_ENTITY_ID']
			));
		}
		else
		{
			$this->addError('TASK_LIST_TASK_ACTION_DENIED');
		}

		return array();
	}

	/**
	 * Apply filter and restart.
	 * @return array
	 */
	protected function actionApplyFilter()
	{
		// filter will be applied in the getList
		return array(
			'columns' => $this->getColumns(),
			'items' => $this->getData()
		);
	}

	/**
	 * Change group and restart.
	 * @return array
	 */
	protected function actionChangeGroup()
	{
		// remember group and filter will be applied in the getList
		if ($this->arParams['GROUP_ID'] > 0)
		{
			\Bitrix\Socialnetwork\WorkgroupViewTable::set(array(
				'GROUP_ID' => $this->arParams['GROUP_ID'],
				'USER_ID' => $this->userId
			));
		}
		$isAdmin = $this->isAdmin();
		return array(
			'columns' => $this->getColumns(),
			'items' => $this->getData(),
			'canAddColumn' => $isAdmin,
			'canEditColumn' => $isAdmin,
			'canRemoveColumn' => $isAdmin,
			'canAddItem' => $this->canCreateTasks(),
			'canSortItem' => $this->canSortTasks(),
			'newTaskOrder' => $this->getNewTaskOrder(),
			'admins' => !$isAdmin ? array_values($this->getAdmins()) : array()
		);
	}

	/**
	 * Change demo view to other.
	 * @return array
	 */
	protected function actionChangeDemoView()
	{
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
	protected function actionNotifyAdmin()
	{
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
	protected function actionSetNewTaskOrder()
	{
		$order = $this->request('order');
		$groupId = $this->arParams['GROUP_ID'];

		if (
			($order = $this->request('order')) &&
			($order == 'asc' || $order == 'desc') &&
			$this->canSortTasks()
		)
		{
			if ($groupId > 0 && $this->arParams['PERSONAL'] != 'Y')
			{
				ProjectsTable::set($groupId, array(
					'ORDER_NEW_TASK' => $order
				));
			}
			else
			{
				\CUserOptions::setOption('tasks', 'order_new_task', $order);
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
	protected function actionDelegateTask()
	{
		if (
			($taskId = $this->request('taskId')) &&
			($responsible = $this->request('userId'))
		)
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			$taskData = $task->getData();
			if (
				$taskData['CREATED_BY'] == $this->userId &&
				$task->isActionAllowed(\CTaskItem::ACTION_EDIT)
			)
			{
				$task->update(array(
					'RESPONSIBLE_ID' => $responsible
				));
			}
			elseif ($task->isActionAllowed(\CTaskItem::ACTION_DELEGATE))
			{
				$task->delegate($responsible);
			}
			elseif ($task->isActionAllowed(\CTaskItem::ACTION_EDIT))
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
			else
			{
				return $this->getRawData($taskId, $this->request('columnId'));
			}
		}

		return array();
	}

	/**
	 * Delegate author's rights of task to other.
	 * @return array
	 */
	protected function actionChangeAuthorTask()
	{
		if (
			($taskId = $this->request('taskId')) &&
			($author = $this->request('userId'))
		)
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			if ($task->isActionAllowed(\CTaskItem::ACTION_EDIT))
			{
				try
				{
					$task->update(array('CREATED_BY' => $author));
				}
				catch (\TasksException $e)
				{
					$this->addError($e->getMessageOrigin());
					return array();
				}
			}
			//tmp, bug #85959
			if (false && ($e = $this->application->GetException()))
			{
				$this->addError($e->getString());
			}
			else
			{
				return $this->getRawData($taskId, $this->request('columnId'), false);
			}
		}

		return array();
	}

	/**
	 * Set deadline to the task.
	 * @return array
	 */
	protected function actionDeadlineTask()
	{
		if (
			($taskId = $this->request('taskId')) &&
			($deadline = $this->request('deadline'))
		)
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			if ($task->isActionAllowed(\CTaskItem::ACTION_CHANGE_DEADLINE))
			{
				$update = array(
					'DEADLINE' => $deadline
				);
				$fields = $task->getData();
				$deadline = new DateTime($deadline);
				// if date start great then deadline
				if ($fields['START_DATE_PLAN'] != '')
				{
					$fields['START_DATE_PLAN'] = new DateTime($fields['START_DATE_PLAN']);
					if ($fields['START_DATE_PLAN']->getTimestamp() >= $deadline->getTimestamp())
					{
						$update['START_DATE_PLAN'] = DateTime::createFromTimestamp($deadline->getTimestamp() - $this->timeOffset - 3600);
					}
				}
				// update
				try
				{
					$task->update($update);
				}
				catch (\TasksException $e)
				{
					$message = $e->getMessage();
					$data = current(unserialize($message));
					$this->addError($data['text']);

					return array();
				}
			}
			else
			{
				$this->addError('TASK_LIST_ERROR_CHANGE_DEADLINE');
				return array();
			}
			/*if (($e = $this->application->GetException()))
			{
				$this->addError($e->getString());
				return array();
			}*/
			return $this->getRawData($taskId, $this->request('columnId'));
		}

		return array();
	}

	/**
	 * Group similar actions.
	 * @param string $code Code of action.
	 * @return void
	 */
	protected function actionsSimilar($code)
	{
		if (($taskId = $this->request('taskId')))
		{
			$task = \CTaskItem::getInstance($taskId, $this->userId);
			switch ($code)
			{
				case 'start':
				case 'pause':
					if ($task->isActionAllowed($code == 'start' ? \CTaskItem::ACTION_START : \CTaskItem::ACTION_PAUSE))
					{
						if ($code == 'start')
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
					if ($task->isActionAllowed(\CTaskItem::ACTION_COMPLETE) ||
						$task->isActionAllowed(\CTaskItem::ACTION_APPROVE))
					{
						$task->complete();
					}
					break;
			}
			//tmp, bug #85959
			if (false && ($e = $this->application->GetException()))
			{
				$this->addError($e->getString());
			}
			else
			{
				return $this->getRawData($taskId, $this->request('columnId'));
			}
		}

		return array();
	}

	/**
	 * Start task exection.
	 * @return array
	 */
	protected function actionStartTask()
	{
		return $this->actionsSimilar('start');
	}

	/**
	 * Pause task exection.
	 * @return array
	 */
	protected function actionPauseTask()
	{
		return $this->actionsSimilar('pause');
	}

	/**
	 * Complete the task.
	 * @return array
	 */
	protected function actionCompleteTask()
	{
		return $this->actionsSimilar('complete');
	}

	/**
	 * Converter tasks of My Plan.
	 * @return array
	 */
	protected function actionConverterMP()
	{
		$params = $this->arParams;

		if ($params['PERSONAL'] == 'Y')
		{
			$lastId = $this->request('last');
			$checkStages = StagesTable::getStages(
				$params['STAGES_ENTITY_ID'],
				true
			);
			list($rows, $res) = $this->getList(array(
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
					if (is_array($checkTS[$row['ID']]) && count($checkTS[$row['ID']]) > 1)
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
	}
}