<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
/** This is alfa version of component! Don't use it! */
/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Helper\Grid;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskListComponent extends TasksBaseComponent
{
	/** @var Filter */
	protected $filter;
	/** @var Grid */
	protected $grid;

	protected $exportAs = false;
	protected $pageSizes = array(
		array("NAME" => "5", "VALUE" => "5"),
		array("NAME" => "10", "VALUE" => "10"),
		array("NAME" => "20", "VALUE" => "20"),
		array("NAME" => "50", "VALUE" => "50"),
		array("NAME" => "100", "VALUE" => "100"),
		//Temporary limited by 100
		//array("NAME" => "200", "VALUE" => "200"),
	);
	protected $listParameters = array();

	// Checks

	public static function getAllowedMethods()
	{
		return array(
			'setViewState'
		);
	}

	public static function setViewState(array $state)
	{
		$filter = Filter::getInstance(\Bitrix\Tasks\Util\User::getId());
		$stateInstance = $filter->getListStateInstance(); // todo
		$stateInstance->setState($state);

		$stateInstance->saveState();

		return array();
	}

	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors,
												   array $auxParams = array())
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$errors->add(
				'SOCIALNETWORK_MODULE_NOT_INSTALLED',
				Loc::getMessage("TASKS_TL_SOCIALNETWORK_MODULE_NOT_INSTALLED")
			);
		}

		if (!Loader::includeModule('forum'))
		{
			$errors->add('FORUM_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TL_FORUM_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors,
												   array $auxParams = array())
	{
		static::tryParseIntegerParameter(
			$arParams['GROUP_ID'],
			0
		); // GROUP_ID > 0 indicates we display this component inside a socnet group

		$arParams['IS_MOBILE'] = (array_key_exists('PATH_TO_SNM_ROUTER', $arParams));

		return $errors->checkNoFatals();
	}

	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors,
											   array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		// check group access here
		if ($arParams["GROUP_ID"] > 0)
		{
			// can we see all tasks in this group?
			$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				array($arParams['GROUP_ID']),
				'tasks',
				'view_all'
			);

			$canViewGroup = is_array($featurePerms) &&
							isset($featurePerms[$arParams['GROUP_ID']]) &&
							$featurePerms[$arParams['GROUP_ID']];

			if (!$canViewGroup)
			{
				// okay, can we see at least our own tasks in this group?
				$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
					SONET_ENTITY_GROUP,
					array($arParams['GROUP_ID']),
					'tasks',
					'view'
				);
				$canViewGroup = is_array($featurePerms) &&
								isset($featurePerms[$arParams['GROUP_ID']]) &&
								$featurePerms[$arParams['GROUP_ID']];
			}

			if (!$canViewGroup)
			{
				$errors->add('ACCESS_TO_GROUP_DENIED', Loc::getMessage('TASKS_TL_ACCESS_TO_GROUP_DENIED'));
			}
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseIntegerParameter($arParams['GROUP_ID'], 0);

		static::tryParseIntegerParameter($arParams['FORUM_ID'], 0); // forum id to keep comments in
		if ($arParams['FORUM_ID'])
		{
			__checkForum($arParams["FORUM_ID"]);
		}

		static::tryParseStringParameter($arParams['SCRUM_BACKLOG'], 'N'); // use tasks list as scrum backlog
		if ($arParams['GROUP_ID'] <= 0)
		{
			$arParams['SCRUM_BACKLOG'] = 'N';
		}

		static::tryParseIntegerParameter(
			$arParams['USER_ID'],
			$this->userId
		); // allows to see other user`s tasks, if have permissions

		$this->exportAs = array_key_exists('EXPORT_AS', $_REQUEST) ? $_REQUEST['EXPORT_AS'] : false;
		if ($this->exportAs !== false)
		{
			$arParams['USE_PAGINATION'] = false;
			$arParams['PAGINATION_PAGE_SIZE'] = 0;
		}
		else
		{
			static::tryParseBooleanParameter(
				$arParams['USE_PAGINATION'],
				true
			); // enable or disable CDResult-driven page navigation in this component
			static::tryParseNonNegativeIntegerParameter($arParams['PAGINATION_PAGE_SIZE'], 10); // lines-on-page amount
		}
	}

	protected function needGroupByGroups()
	{
		return $this->arParams['GROUP_ID'] == 0;
	}

	protected function isGroupByProjectMode()
	{
		$listState = \CTaskListState::getInstance(User::getId());
		$submodes = $listState->getSubmodes();

		return $submodes['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] == 'Y';
	}

	protected function needGroupBySubTasks()
	{
		$submodes = \CTaskListState::getInstance(User::getId())->getSubmodes();

		return $submodes['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] == 'Y';
	}

	protected function doPreAction()
	{
		$this->grid = Grid::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
		$this->filter = Filter::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);

		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_GROUPS'],
			$this->needGroupByGroups() ? 'Y' : 'N'
		);
		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_SUBTASKS'],
			$this->needGroupBySubTasks() ? 'Y' : 'N'
		);

		$this->arResult['GROUP_BY_PROJECT'] = $this->isGroupByProjectMode();

		$this->arParams['DEFAULT_ROLEID'] = $this->filter->getDefaultRoleId();

		static::tryParseStringParameter($this->arParams['FILTER_ID'], $this->filter->getId());
		static::tryParseStringParameter($this->arParams['GRID_ID'], $this->grid->getId());

		$this->arResult['MESSAGES'] = array();

		$calendarSettings = $this->getCalendarSettings();
		$this->arResult['CALENDAR_SETTINGS'] = $calendarSettings['CALENDAR_SETTINGS'];
		$this->arResult['COMPANY_WORKTIME'] = $calendarSettings['COMPANY_WORKTIME'];

		$this->arResult["FILTER"] = $this->filter->getFilters();
		$this->arResult["PRESETS"] = $this->filter->getPresets();

		$this->listParameters['filter'] = $this->arParams['IS_MOBILE'] ? array() : $this->filter->process(); //TODO!

		$this->processGroupActions();

		if ($this->needGroupBySubTasks())
		{
			//TODO!!!
			if (\Bitrix\Main\Grid\Context::isInternalRequest() &&
				check_bitrix_sessid() &&
				$_REQUEST['action'] == \Bitrix\Main\Grid\Actions::GRID_GET_CHILD_ROWS)
			{
				if (!empty($_REQUEST['parent_id']))
				{
					$this->listParameters['filter']['PARENT_ID'] = $_REQUEST['parent_id'];
				}
				unset($this->listParameters['filter']['ONLY_ROOT_TASKS']); // HACK
			}
			else
			{
				$expandedIds = $this->grid->getOptions()->getExpandedRows();

				if ($expandedIds)
				{
					$arrFilter['META:PARENT_ID_OR_NULL'] = array_filter(
						array_unique(
							array_map(
								function($expandedId)
								{
									if (strpos($expandedId, 'group_') === false)
									{
										return $expandedId;
									}
								},
								$expandedIds
							)
						)
					);

					if (empty($this->listParameters['filter']['META:PARENT_ID_OR_NULL']))
					{
						unset($this->listParameters['filter']['META:PARENT_ID_OR_NULL']);
					}
				}
			}
		}
		else
		{
			unset($this->listParameters['filter']['ONLY_ROOT_TASKS']);
		}

		return true;
	}

	private function getCalendarSettings()
	{
		$site = CSite::GetByID(SITE_ID)->Fetch();

		$weekDay = $site['WEEK_START'];
		$weekDaysMap = array(
			'SU',
			'MO',
			'TU',
			'WE',
			'TH',
			'FR',
			'SA'
		);

		$wh = array(
			'HOURS'      => array(
				'START' => array('H' => 9, 'M' => 0, 'S' => 0),
				'END'   => array('H' => 19, 'M' => 0, 'S' => 0),
			),
			'HOLIDAYS'   => array(),
			'WEEKEND'    => array('SA', 'SU'),
			'WEEK_START' => (string)$weekDay != '' && isset($weekDaysMap[$weekDay]) ? $weekDaysMap[$weekDay] : 'MO'
		);

		if (\Bitrix\Main\Loader::includeModule('calendar'))
		{
			$calendarSettings = \CCalendar::GetSettings(array('getDefaultForEmpty' => false));

			if (is_array($calendarSettings['week_holidays']))
			{
				$wh['WEEKEND'] = $calendarSettings['week_holidays'];
			}
			if ((string)$calendarSettings['year_holidays'] != '')
			{
				$holidays = explode(',', $calendarSettings['year_holidays']);
				if (is_array($holidays) && !empty($holidays))
				{
					foreach ($holidays as $day)
					{
						$day = trim($day);
						list($day, $month) = explode('.', $day);
						$day = intval($day);
						$month = intval($month);

						if ($day && $month)
						{
							$wh['HOLIDAYS'][] = array('M' => $month, 'D' => $day);
						}
					}
				}
			}

			$time = explode('.', (string)$calendarSettings['work_time_start']);
			if (intval($time[0]))
			{
				$wh['HOURS']['START']['H'] = intval($time[0]);
			}
			if (intval($time[1]))
			{
				$wh['HOURS']['START']['M'] = intval($time[1]);
			}

			$time = explode('.', (string)$calendarSettings['work_time_end']);
			if (intval($time[0]))
			{
				$wh['HOURS']['END']['H'] = intval($time[0]);
			}
			if (intval($time[1]))
			{
				$wh['HOURS']['END']['M'] = intval($time[1]);
			}
		}

		return array(
			'CALENDAR_SETTINGS' => $wh,
			'COMPANY_WORKTIME'  => $wh['HOURS']
		);
	}

	protected function processGroupActions()
	{
		if (!Bitrix\Main\Grid\Context::isInternalRequest() || !check_bitrix_sessid())
		{
			return false;
		}

		$request = static::getRequest(true);
		$controls = $request->get('controls');
		if (!$controls || !is_array($controls))
		{
			return false;
		}

		$rows = $request->get('rows');
		$action = $controls['action_button_'.$this->arParams['GRID_ID']];

		$allTasks = array_key_exists('action_all_rows_'.$this->arParams['GRID_ID'], $controls) &&
					$controls['action_all_rows_'.$this->arParams['GRID_ID']] == 'Y';

		if ($allTasks)
		{
			$parameters = array('ERRORS' => $this->errors);

			$getListParameters = array(
				'select'       => array('ID'),
				'legacyFilter' => array_merge($this->listParameters['filter'], array('ONLY_ROOT_TASKS' => 'N'))
			);
			$mgrResult = Manager\Task::getList($this->userId, $getListParameters, $parameters);
			$rows = array();
			foreach ($mgrResult['DATA'] as $item)
			{
				$rows[] = $item['ID'];
			}
		}

		$auxParams = array(
			'QUERY_TYPE' => static::QUERY_TYPE_AJAX,
			'CLASS_NAME' => static::getComponentClassName(),
			'REQUEST'    => $request
		);

		$arParams = array();
		$errors = new Collection();

		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$todo = array();

		$arguments = array();

		switch ($action)
		{
			case 'setgroup':
				$arguments['groupId'] = (int)$controls['groupId'];
				break;
			case 'setoriginator':
				$arguments['originatorId'] = (int)$controls['originatorId'];
				break;
			case 'setresponsible':
				$arguments['responsibleId'] = (int)$controls['responsibleId'];
				break;
			case 'addaccomplice':
				$arguments['accompliceId'] = (int)$controls['accompliceId'];
				break;
			case 'addauditor':
				$arguments['auditorId'] = (int)$controls['auditorId'];
				break;
			case 'setdeadline':
				$arguments['newDeadline'] = $controls['ACTION_SET_DEADLINE_from'];
				break;
			case 'setsprint':
				// at first create new sprint
				if ($this->arParams['SCRUM_BACKLOG'] == 'Y')
				{
					$res = \Bitrix\Tasks\Kanban\SprintTable::createNext(
						$this->arParams['GROUP_ID'],
						new \Bitrix\Main\Type\DateTime(
							$controls['ACTION_SET_SPRINT_from']
						)
					);
					if (!$res->isSuccess())
					{

						$this->arResult['MESSAGES'] = array(
							'TYPE' => \Bitrix\Main\Grid\MessageType::ERROR,
							'TITLE' => GetMessage('TASKS_GROUP_ACTION_ERROR_TITLE'),
							'TEXT' => implode("\n", $res->getErrorMessages())
						);
						return;
					}
					else
					{
						$arguments['sprintId'] = $res->getId();
					}
				}
				break;
			case 'substractdeadline':
			case 'adjustdeadline':
				$arguments['type'] = $controls['type'];
				$arguments['num'] = (int)$controls['num'];

				if(!$arguments['num'])
				{
					$this->arResult['MESSAGES'] = array(
						"TYPE" => \Bitrix\Main\Grid\MessageType::ERROR,
						"TITLE" => GetMessage('TASKS_GROUP_ACTION_DAYS_NUM_INVALID_TITLE'),
						"TEXT" => GetMessage('TASKS_GROUP_ACTION_DAYS_NUM_INVALID_TEXT')
					);

					return;
				}
				break;
			case 'settaskcontrol':
				$arguments['value'] = $controls['value'];
				break;
		}

		foreach ($rows as $rowId)
		{
			$arguments['id'] = $rowId;

			$todo[] = array(
				'OPERATION'  => 'task.'.$action,
				'ARGUMENTS'  => $arguments,
				'PARAMETERS' => $arParams
			);
		}
		$plan->import($todo);

		static::dispatch($plan, $errors, $auxParams, $arParams);

		$errorsList = array();

		/** @var Bitrix\Tasks\Dispatcher\ToDo $item */
		foreach ($plan as $item)
		{
			$result = $item->getResult();

			if (!$result)
			{
				continue;
			}

			$errors = $result->getErrors();
			$args = $item->getArguments();

			if ($errors->count() > 0)
			{
				/** @var Bitrix\Tasks\Util\Error $err */
				foreach ($errors as $err)
				{
					$errorsList[$err->getMessage()][] = $args['id'];
				}
			}
		}

		if (!empty($errorsList))
		{
			foreach ($errorsList as $error => $taskIds)
			{
				$this->arResult['MESSAGES'][] = array(
					"TYPE" => \Bitrix\Main\Grid\MessageType::MESSAGE,
					"TITLE" => GetMessage('TASKS_GROUP_ACTION_ERROR_TITLE'),
					"TEXT" => GetMessage(
						'TASKS_GROUP_ACTION_ERROR_MESSAGE',
						array(
							'#MESSAGE#' => $error,
							'#TASK_IDS#' => join(', ', $taskIds)
						)
					)
				);
			}
		}

		return $errors->checkNoFatals();
	}

	protected function doPostAction()
	{
		$this->arResult['DEFAULT_PRESET_KEY'] = $this->filter->getDefaultPresetKey();

		$this->arParams['COLUMNS'] = $this->getSelect();
		$this->arParams['UF'] = $this->getUF();

		$listState = \CTaskListState::getInstance(User::getId());
		$this->arParams['VIEW_STATE'] = $listState->getState();

		if ($this->userId != $this->arParams['USER_ID'])
		{
			$users = \Bitrix\Tasks\Util\User::getData(array($this->arParams['USER_ID']));
			$this->arResult['USER'] = $users[$this->arParams['USER_ID']];
		}

		$order = $this->getOrder();
		unset($order['GROUP_ID']);
		$sortFields = array_keys($order);
		$this->arParams['SORT_FIELD'] = $sortFields[0];

		$fieldDir = $order[$this->arParams['SORT_FIELD']];
		if (!$fieldDir)
		{
			$fieldDir = 'asc';
		}
		else
		{
			$fieldDir = explode(',', $fieldDir);
			$fieldDir = $fieldDir[0];
		}
		$this->arParams['SORT_FIELD_DIR'] = $fieldDir;

		$this->arResult["CAN"] = array(
			"SORT" => $this->canSortTasks()
		);


		$oTimer = CTaskTimerManager::getInstance(\Bitrix\Tasks\Util\User::getId());
		$this->arParams['TIMER']  = $oTimer->getRunningTask(true);	// false => allow use static cache
	}

	protected function getSelect()
	{
		$columns = $this->grid->getVisibleColumns();

		if ($this->exportAs == false)
		{
			if ($this->needGroupBySubTasks())
			{
				$columns[] = 'PARENT_ID';
			}

			$columns[] = 'PRIORITY';
			$columns[] = 'FAVORITE';
			$columns[] = 'COMMENTS_COUNT';
			$columns[] = 'ALLOW_CHANGE_DEADLINE';
			$columns[] = 'ALLOW_TIME_TRACKING';
			$columns[] = 'TIME_SPENT_IN_LOGS';
			$columns[] = 'TIME_ESTIMATE';
			$columns[] = 'VIEWED_DATE';

			$columns = array_merge($columns, array_keys($this->getUF()));
		}

		return array_unique($columns);
	}

	/**
	 * @return \Bitrix\Tasks\Util\UserField|array|null|string
	 */
	private function getUF()
	{
		$uf = \Bitrix\Tasks\Item\Task::getUserFieldControllerClass();

		$scheme = $uf::getScheme();
		unset($scheme['UF_TASK_WEBDAV_FILES'], $scheme['UF_MAIL_MESSAGE']);

		return $scheme;
	}

	protected function getOrder()
	{
		$gridSort = array();
		$sortResult = array();

		if ($this->isGroupByProjectMode())
		{
			$sortResult['GROUP_ID'] = 'asc';
		}

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		// for scrum backlog we force user's sorting
		if ($this->arParams['SCRUM_BACKLOG'] == 'Y')
		{
			$gridSort['SORTING'] = 'asc';
		}
		else if ($request->get('SORTF') != null &&
			in_array($request->get('SORTF'), \Bitrix\Tasks\Ui\Controls\Column::getFieldsForSorting())
		)
		{
			$sortResult[$request->get('SORTF')] = $request->get('SORTD') ? $request->get('SORTD') : 'asc';
			$this->grid->getOptions()->setSorting($request->get('SORTF'), $sortResult[$request->get('SORTF')]);
			$this->grid->getOptions()->save();
		}
		else
		{
			$gridSort = $this->grid->getOptions()->GetSorting(
				array(
					'sort' => array('DEADLINE' => 'desc,nulls', 'ID' => 'asc'),
					'vars' => array('by' => 'by', 'order' => 'order')
				)
			);
			$gridSort = $gridSort['sort'];
		}

		if (isset($gridSort["SORTING"]))
		{
			//			$sortResult = array_merge(
			//				$sortResult,
			//				array(
			//					"SORTING"         => "asc",
			//					"STATUS_COMPLETE" => "asc",
			//					"DEADLINE"        => "asc,nulls",
			//					"ID"              => "asc",
			//				)
			//			);
			$sortResult = array(
				"SORTING" => "asc"
			);
		}
		else
		{
			$sortResult = array_merge($sortResult, $gridSort);

			if (!array_key_exists('ID', $sortResult))
			{
				$sortResult['ID'] = 'asc';
			}

			foreach ($sortResult as $key => &$value)
			{
				if (in_array($key, array('DEADLINE')))
				{
					$value = $value.',nulls';
				}
			}
		}

		return $sortResult;
	}

	private function canSortTasks()
	{
		$currentGroupId = $this->arParams["GROUP_ID"];
		$canSortTasks = false;

		if ($currentGroupId)
		{
			$canSortTasks = $this->arParams["SORT_FIELD"] === "SORTING" &&
							SocialNetwork\Group::can($currentGroupId, SocialNetwork\Group::ACTION_SORT_TASKS);
		}
		else
		{
			$canSortTasks = $this->arParams["SORT_FIELD"] === "SORTING" && $this->userId == $this->arParams["USER_ID"];
		}

		return $canSortTasks;
	}

	protected function mergeWithTags(array $items)
	{
		if (empty($items))
		{
			return array();
		}

		$res = \Bitrix\Tasks\Internals\Task\TagTable::getList(array(
			'select' => array(
				'TASK_ID', 'NAME'
			),
			'filter' => array(
				'TASK_ID' => array_keys($items)
			)
		));

		while ($row = $res->fetch())
		{
			$items[ $row['TASK_ID'] ]['TAG'][] = $row['NAME'];
		}

		return $items;
	}

	/**
	 * Collapse data by parents and return new array.
	 * @param array $data Input data array.
	 * @return array
	 */
	protected function collapseParents(array $data)
	{
		//return $data;
		$collapsed = false;
		foreach ($data as $id => &$item)
		{
			if (
				$item['PARENT_ID'] > 0 &&
				(
					!isset($data[$item['PARENT_ID']]['PARENT_ID']) ||
					$data[$item['PARENT_ID']]['PARENT_ID'] == 0
				)
			)
			{
				if (!isset($item['NAV_CHAIN']))
				{
					$item['NAV_CHAIN'] = [];
				}
				$item['NAV_CHAIN'][] = $data[$item['PARENT_ID']];
				if (isset($data[$item['PARENT_ID']]['NAV_CHAIN']))
				{
					$item['NAV_CHAIN'] = array_merge(
						$item['NAV_CHAIN'],
						$data[$item['PARENT_ID']]['NAV_CHAIN']
					);
					foreach ($item['NAV_CHAIN'] as &$navItem)
					{
						$navItem['NAV_CHAIN'] = [];
					}
					unset($navItem);
				}
				$data[$item['PARENT_ID']]['REMOVE'] = true;
				$item['PARENT_ID'] = 0;
				$collapsed = true;
			}
		}
		unset($item);

		if ($collapsed)
		{
			$data = $this->collapseParents($data);
		}
		else
		{
			foreach ($data as $id => $item)
			{
				if (
					isset($item['REMOVE']) &&
					$item['REMOVE']
				)
				{
					unset($data[$id]);
				}
			}
		}

		return $data;
	}

	protected function getData()
	{
		$this->grid->getOptions()->resetExpandedRows();

		$parameters = ['ERRORS' => $this->errors];
		$parameters['MAKE_ACCESS_FILTER'] = true;

		//region NAV
		$navPageSize = $this->getPageSize();
		//endregion

		$getListParameters = [
			'order'        => $this->getOrder(),
			'select'       => $this->getSelect(),
			'legacyFilter' => $this->listParameters['filter'],
		];


		if ($this->exportAs === false)
		{
			$getListParameters['NAV_PARAMS'] = [
				'nPageSize'          => $navPageSize,
				'bDescPageNumbering' => false,
				'NavShowAll'         => false,
				'bShowAll'           => false,
				'showAlways'         => false,
				'SHOW_ALWAYS'        => false
			];
		}

		if (isset($this->listParameters['filter']['PARENT_ID']))
		{
			$getListParameters['NAV_PARAMS']['NavShowAll'] = true;
		}

		if (array_key_exists('clear_nav', $_REQUEST) && $_REQUEST['clear_nav'] == 'Y')
		{
			$getListParameters['NAV_PARAMS']['iNumPage'] = 1;
		}

		// @todo: needed to refactor
		if ($this->arParams['SCRUM_BACKLOG'] == 'Y')
		{
			$getListParameters['legacyFilter']['ONLY_ROOT_TASKS'] = 'N';
			$getListParameters['NAV_PARAMS']['nPageSize'] = 500;
		}

		$mgrResult = Manager\Task::getList($this->userId, $getListParameters, $parameters);

		if (array_key_exists('TAG', array_flip($getListParameters['select'])))
		{
			$mgrResult['DATA'] = $this->mergeWithTags($mgrResult['DATA']);
		}

		$this->arResult['LIST'] = $mgrResult['DATA'];
		$this->arResult['GET_LIST_PARAMS'] = $getListParameters;
		$this->arResult['SUB_TASK_COUNTERS'] = $this->processSubTaskCounters();

		if ($this->arParams['SCRUM_BACKLOG'] == 'Y')
		{
			$this->arResult['LIST'] = $this->collapseParents(
				$this->arResult['LIST']
			);
		}

		//region NAV
		$this->arResult['NAV_OBJECT'] = $mgrResult['AUX']['OBJ_RES'];
		$this->arResult['NAV_OBJECT']->NavStart($navPageSize, false);
		$this->arResult['PAGE_SIZES'] = $this->pageSizes;
		$this->arResult['TOTAL_RECORD_COUNT'] = $this->arResult['NAV_OBJECT']->NavRecordCount;
		//endregion

		if ($this->errors->checkHasFatals())
		{
			return;
		}
	}

	protected function getPageSize()
	{
		$navParams = $this->grid->getOptions()->getNavParams(array('nPageSize' => 50));

		return (int)$navParams['nPageSize'];
	}

	private function processSubTaskCounters()
	{
		$counters = array();

		if ($this->needGroupBySubTasks())
		{
			$taskIds = array();
			foreach ($this->arResult['LIST'] as $item)
			{
				$taskIds[] = $item['ID'];
			}

			if (!empty($taskIds))
			{
				$params = $this->listParameters['filter'];
				unset($params['META:PARENT_ID_OR_NULL']);
				$rsCount = \CTasks::GetChildrenCount($params, $taskIds);
				while ($item = $rsCount->Fetch())
				{
					$counters[$item['PARENT_ID']] = $item['CNT'];
				}
			}
		}

		return $counters;
	}

	private function getSubTasks($taskId, $level = 0)
	{
		$list = array();

		if(\CTasks::getTaskSubTree($taskId))
		{
			$this->listParameters['filter']['PARENT_ID'] = $taskId;
			$getListParameters = array(
				'order'        => $this->getOrder(),
				'select'       => $this->getSelect(),
				'legacyFilter' => $this->listParameters['filter'],
			);

			$mgrResult = Manager\Task::getList($this->userId, $getListParameters);
			$level ++;

			if($mgrResult['DATA'])
			{
				foreach ($mgrResult['DATA'] as $item)
				{
					$item['__LEVEL'] = $level;
					$list[] = $item;
					if ($sub = $this->getSubTasks($item['ID'], $level))
					{
						$list = array_merge($list, $sub);
					}
				}
			}
		}

		return $list;
	}

	protected function display()
	{
		global $APPLICATION;

		if($this->errors->checkNoFatals())
		{
			if ($this->exportAs)
			{
				$APPLICATION->RestartBuffer();

				//region SUB TASKS IN EXPORT EXCELL
				$list = array();
				foreach($this->arResult['LIST'] as $item)
				{
					$list[] = $item;

					if($sub = $this->getSubTasks($item['ID']))
					{
						$list = array_merge($list, $sub);
					}
				}

				$this->arResult['LIST'] = $list;
				//endregion

				$this->IncludeComponentTemplate('export_'.strtolower($this->exportAs));

				parent::doFinalActions();
			}
			else
			{
				$this->includeComponentTemplate();
			}
		}
		else
		{
			foreach($this->errors as $error)
			{
				ShowError($error);
			}
			return;
		}
	}
}