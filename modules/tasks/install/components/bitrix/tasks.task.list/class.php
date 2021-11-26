<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2025 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Tasks\Access;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Helper\Grid;
use Bitrix\Tasks\Integration\Disk\Connector\Task as ConnectorTask;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\TagTable;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Grid\Task;
use Bitrix\Tasks\TourGuide;
use Bitrix\Tasks\Ui\Controls\Column;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskListComponent extends TasksBaseComponent
{
	private const STORAGE_KEY = 'TASK_LIST_PAGING';

	/** @var Filter */
	protected $filter;
	/** @var Grid */
	protected $grid;

	protected $exportAs = false;
	protected $getChildRowsAction = false;
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
		return [
			'setViewState',
			'getNearTasks',
			'prepareGridRowsForTasks',
			'getTotalCount',
		];
	}

	/**
	 * @param array $state
	 * @return array
	 */
	public static function setViewState(array $state): array
	{
		$userId = User::getId();
		$stateInstance = Filter::getInstance($userId)->getListStateInstance(); // todo
		if ($stateInstance)
		{
			$stateInstance->setState($state);
			$stateInstance->saveState();
		}

		return [];
	}

	/**
	 * @param $taskId
	 * @param array $navigation
	 * @param array $arParams
	 * @return bool[]
	 */
	public static function getNearTasks($taskId, array $navigation, array $arParams = []): array
	{
		/** @var Filter $filter */
		$filter = Filter::getInstance($arParams['USER_ID'], $arParams['GROUP_ID']);

		$pageNumber = $navigation['pageNumber'];
		$pageSize = $navigation['pageSize'];

		$getListParameters = [
			'select' => ['ID'],
			'legacyFilter' => $filter->process(),
			'order' => $arParams['GET_LIST_PARAMETERS']['order'],
			'NAV_PARAMS' => [
				'iNumPage' => $pageNumber,
				'iNumPageSize' => ($pageNumber - 1) * $pageSize,
				'nPageSize' => $pageSize,
			],
		];
		$parameters = [
			'RETURN_ACCESS' => 'N',
			'USE_MINIMAL_SELECT_LEGACY' => 'N',
			'MAKE_ACCESS_FILTER' => true,
		];

		$falseResult = [
			'before' => false,
			'after' => false,
		];

		$tasks = array_keys(Manager\Task::getList($arParams['USER_ID'], $getListParameters, $parameters)['DATA']);
		if (empty($tasks) || ($index = array_search((int)$taskId, $tasks, true)) === false)
		{
			return $falseResult;
		}

		return [
			'before' => ($index === count($tasks) - 1 ? false : $tasks[$index + 1]),
			'after' => ($index === 0 ? false : $tasks[$index - 1]),
		];
	}

	/**
	 * @param array $taskIds
	 * @param array $data
	 * @param array $arParams
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function prepareGridRowsForTasks(array $taskIds, array $data = [], array $arParams = []): array
	{
		static::tryParseIntegerParameter($arParams['USER_ID'], User::getId());

		$arParams['CAN_SEE_COUNTERS'] = self::canSeeCounters((int)$arParams['USER_ID']);

		if (empty($data))
		{
			$parameters = [
				'MAKE_ACCESS_FILTER' => true,
				'TARGET_USER_ID' => (int)$arParams['USER_ID']
			];
			$getListParameters = [
				'select' => array_keys(\CTasks::getFieldsInfo()),
				'legacyFilter' => ['ID' => $taskIds],
			];
			$tasks = Manager\Task::getList(User::getId(), $getListParameters, $parameters)['DATA'];
		}
		else
		{
			$converter = new Converter(
				Converter::TO_UPPER
				| Converter::TO_SNAKE
				| Converter::KEYS
				| Converter::RECURSIVE
			);
			$tasks = $converter->process($data);
		}
		$tasks = self::setGroupData($tasks);
		$tasks = self::setFilesCount($tasks);
		$tasks = self::setCheckListCount($tasks);

		$tagResult = TagTable::getList([
			'select' => ['TASK_ID', 'NAME'],
			'filter' => ['TASK_ID' => array_keys($tasks)],
		]);
		while ($tag = $tagResult->fetch())
		{
			$taskId = $tag['TASK_ID'];
			$tasks[$taskId]['TAG'][] = $tag['NAME'];
		}

		if (array_key_exists('FILTER_ID', $arParams))
		{
			$arParams['FILTER_FIELDS'] = (new Options($arParams['FILTER_ID']))->getFilter();
		}

		return (new Task\Grid($tasks, $arParams))->prepareRows();
	}

	protected static function checkRequiredModules(
		array &$arParams,
		array &$arResult,
		Collection $errors,
		array $auxParams = []
	)
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
			$errors->add(
				'FORUM_MODULE_NOT_INSTALLED',
				Loc::getMessage("TASKS_TL_FORUM_MODULE_NOT_INSTALLED")
			);
		}

		return $errors->checkNoFatals();
	}

	protected static function checkBasicParameters(
		array &$arParams,
		array &$arResult,
		Collection $errors,
		array $auxParams = []
	)
	{
		// GROUP_ID > 0 indicates we display this component inside a socnet group
		static::tryParseIntegerParameter($arParams['GROUP_ID'], 0);

		return $errors->checkNoFatals();
	}

	protected static function checkPermissions(
		array &$arParams,
		array &$arResult,
		Collection $errors,
		array $auxParams = []
	)
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		$groupId = $arParams['GROUP_ID'];

		// check group access here
		if ($groupId > 0)
		{
			// can we see all tasks in this group?
			$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$groupId],
				'tasks',
				'view_all'
			);
			$canViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];

			if (!$canViewGroup)
			{
				// okay, can we see at least our own tasks in this group?
				$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
					SONET_ENTITY_GROUP,
					[$groupId],
					'tasks',
					'view'
				);
				$canViewGroup = is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId];
			}

			if (!$canViewGroup)
			{
				$errors->add(
					'ACCESS_TO_GROUP_DENIED',
					Loc::getMessage('TASKS_TL_ACCESS_TO_GROUP_DENIED')
				);
			}
		}

		return $errors->checkNoFatals();
	}

	public static function getTotalCount($userId, $groupId, $parameters)
	{
		$userId = (int) $userId;
		$groupId = (int) $groupId;
		if (!$userId)
		{
			return 0;
		}

		$filter = Filter::getInstance($userId, $groupId)->process();

		$listState = \CTaskListState::getInstance($userId);
		$groupBySubtasks = $listState->isSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		if (!$groupBySubtasks)
		{
			unset($filter['ONLY_ROOT_TASKS']);
		}

		/**
		 * Group by subtask should be ignored for fulltext search
		 * See #134428 for more information
		 */
		$listStateIsModified = false;
		if (
			array_key_exists('::SUBFILTER-FULL_SEARCH_INDEX', $filter)
			&& $groupBySubtasks
		)
		{
			unset($filter['ONLY_ROOT_TASKS']);
			$listStateIsModified = true;
			$listState->switchOffSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		}

		$parameters = \Bitrix\Main\Web\Json::decode($parameters);
		if (!array_key_exists('TARGET_USER_ID', $parameters))
		{
			$parameters['TARGET_USER_ID'] = $userId;
		}

		$count = Manager\Task::getCount($filter, $parameters);

		if ($listStateIsModified)
		{
			$listState->switchOnSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		}

		return $count;
	}

	protected function checkParameters()
	{
		parent::checkParameters();

		$this->arParams['IS_MOBILE'] = (array_key_exists('PATH_TO_SNM_ROUTER', $this->arParams));

		// allows to see other user`s tasks, if have permissions
		static::tryParseIntegerParameter($this->arParams['USER_ID'], $this->userId);
		static::tryParseIntegerParameter($this->arParams['GROUP_ID'], 0);
		static::tryParseStringParameter($this->arParams['PROJECT_VIEW'], 'N');

		static::tryParseIntegerParameter($this->arParams['FORUM_ID'], 0); // forum id to keep comments in
		if ($this->arParams['FORUM_ID'])
		{
			__checkForum($this->arParams["FORUM_ID"]);
		}

		$this->exportAs = (array_key_exists('EXPORT_AS', $_REQUEST) ? $_REQUEST['EXPORT_AS'] : false);
		if ($this->exportAs !== false)
		{
			$this->arParams['USE_PAGINATION'] = false;
			$this->arParams['PAGINATION_PAGE_SIZE'] = 0;
		}
		else
		{
			// enable or disable CDResult-driven page navigation in this component
			static::tryParseBooleanParameter($this->arParams['USE_PAGINATION'], true);
			static::tryParseNonNegativeIntegerParameter($this->arParams['PAGINATION_PAGE_SIZE'], 10);
		}

		$this->getChildRowsAction = false;
		if (
			array_key_exists('action', $_REQUEST)
			&& $_REQUEST['action'] === 'getChildRows'
		)
		{
			$this->getChildRowsAction = true;
		}

		static::tryParseBooleanParameter($this->arParams['LAZY_LOAD'], false);
		if (
			$this->arParams['LAZY_LOAD']
			&& \Bitrix\Main\Context::getCurrent()->getRequest()->getQuery('IFRAME') !== 'Y'
		)
		{
			$this->arParams['LAZY_LOAD'] = false;
		}

		$this->arParams['CAN_SEE_COUNTERS'] = self::canSeeCounters($this->arParams['USER_ID']);
	}

	/**
	 * @return bool
	 */
	protected function isMyList(): bool
	{
		return (int)$this->arParams['USER_ID'] === (int)$this->userId;
	}

	/**
	 * @return bool
	 */
	protected function canUsePin(): bool
	{
		return $this->isMyList();
	}

	protected function disableGrouping(string $field, string $direction): void
	{
		if ($this->arParams['PROJECT_VIEW'] !== 'Y')
		{
			return;
		}

		$listState = \CTaskListState::getInstance($this->arParams['USER_ID']);
		if ($listState->isSubmode(\CTaskListState::VIEW_SUBMODE_WITH_GROUPS))
		{
			$listState->switchOffSubmode(\CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
		}
	}

	/**
	 * @return bool
	 */
	protected function needGroupByGroups(): bool
	{
		return $this->arParams['GROUP_ID'] == 0;
	}

	/**
	 * @return bool
	 */
	protected function isGroupByProjectMode(): bool
	{
		$listState = \CTaskListState::getInstance($this->arParams['USER_ID']);
		return $listState->isSubmode(\CTaskListState::VIEW_SUBMODE_WITH_GROUPS);
	}

	/**
	 * @return bool
	 */
	protected function needGroupBySubTasks(): bool
	{
		$listState = \CTaskListState::getInstance($this->arParams['USER_ID']);
		return $listState->isSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
	}

	/**
	 * @return array|string[][]
	 */
	private function getDefaultSorting(): array
	{
		return [
			'sort' => ['ACTIVITY_DATE' => 'desc'],
			'vars' => ['by' => 'by', 'order' => 'order'],
		];
	}

	protected function loadGrid()
	{
		$this->grid = Grid::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
		$this->filter = Filter::getInstance($this->arParams["USER_ID"], $this->arParams["GROUP_ID"]);
	}

	protected function doPreAction()
	{
		parent::doPreAction();

		if (
			$this->exportAs
			&& !Access\TaskAccessController::can($this->userId, Access\ActionDictionary::ACTION_TASK_EXPORT)
		)
		{
			$this->errors->add(
				'ACCESS_DENIED',
				Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'),
				\Bitrix\Tasks\Util\Error::TYPE_FATAL
			);
		}

		$this->loadGrid();

		$this->arResult['USER_ID'] = $this->userId;
		$this->arResult['OWNER_ID'] = $this->arParams['USER_ID'];

		$this->arParams['DEFAULT_ROLEID'] = $this->filter->getDefaultRoleId();

		$order = $this->getOrder();
		unset($order['GROUP_ID'], $order['IS_PINNED'], $order['IS_PINNED_IN_GROUP']);

		reset($order);
		$field = key($order);
		$direction = current($order);
		$direction = ($direction ? explode(',', $direction)[0] : 'asc');

		$this->disableGrouping($field, $direction);

		static::tryParseStringParameter($this->arParams['FILTER_ID'], $this->filter->getId());
		static::tryParseStringParameter($this->arParams['GRID_ID'], $this->grid->getId());
		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_GROUPS'],
			$this->needGroupByGroups() ? 'Y' : 'N'
		);
		static::tryParseStringParameter(
			$this->arParams['NEED_GROUP_BY_SUBTASKS'],
			$this->needGroupBySubTasks() ? 'Y' : 'N'
		);

		$this->arParams['SORT'] = [
			$field => $direction,
		];
		$this->arParams['SORT_FIELD'] = $field;
		$this->arParams['SORT_FIELD_DIR'] = $direction;

		$this->arParams['IS_MY_LIST'] = $this->isMyList();
		$this->arParams['CAN_USE_PIN'] = $this->canUsePin();

		$this->arResult['GROUP_BY_PROJECT'] = $this->isGroupByProjectMode();
		$this->arResult['GROUP_BY_SUBTASK'] = ($this->arParams['NEED_GROUP_BY_SUBTASKS'] === 'Y');
		$this->arResult['MESSAGES'] = [];

		$this->arResult["FILTER"] = $this->filter->getFilters();
		$this->arResult["PRESETS"] = $this->filter->getAllPresets();

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
									if (mb_strpos($expandedId, 'group_') === false)
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

		if (
			Loader::includeModule('socialnetwork')
			&& isset($this->arParams['GROUP_ID'])
			&& $this->arParams['GROUP_ID']
		)
		{
			SocialNetwork::setLogDestinationLast(['SG' => [$this->arParams['GROUP_ID']]]);
		}

		return true;
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
			$mgrResult = Manager\Task::getList($this->arParams['USER_ID'], $getListParameters, $parameters);
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

		$listState = \CTaskListState::getInstance($this->arParams['USER_ID']);
		$this->arParams['VIEW_STATE'] = $listState->getState();

		if (!$this->isMyList())
		{
			$users = \Bitrix\Tasks\Util\User::getData(array($this->arParams['USER_ID']));
			$this->arResult['USER'] = $users[$this->arParams['USER_ID']];
		}

		$this->arResult['CAN'] = ['SORT' => $this->canSortTasks()];
		$this->arResult['SORTING'] = $this->grid->getOptions()->getSorting($this->getDefaultSorting());

		$oTimer = CTaskTimerManager::getInstance($this->arParams['USER_ID']);
		$this->arParams['TIMER']  = $oTimer->getRunningTask(true);	// false => allow use static cache

		$this->processTours();
	}

	private function processTours(): void
	{
		$this->arResult['tours'] = [
			'firstGridTaskCreation' => [
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
			/** @var TourGuide\FirstGridTaskCreation $firstGridTaskTour */
			$firstGridTaskTour = TourGuide\FirstGridTaskCreation::getInstance($this->userId);
			$currentStepPopupData = $firstGridTaskTour->getCurrentStepPopupData();
			$showFirstGridTaskTour = $firstGridTaskTour->proceed();

			if ($showFirstGridTaskTour)
			{
				\Bitrix\Tasks\AnalyticLogger::logToFile(
					'markShowedStep',
					'firstGridTaskCreation',
					'0',
					'tourGuide'
				);
			}

			$this->arResult['tours']['firstGridTaskCreation'] = [
				'show' => $showFirstGridTaskTour,
				'popupData' => $currentStepPopupData,
			];

			if (!$showFirstGridTaskTour && $this->canProceedExpiredTour())
			{
				/** @var TourGuide\ExpiredTasksDeadlineChange $expiredTour */
				$expiredTour = TourGuide\ExpiredTasksDeadlineChange::getInstance($this->userId);
				$currentStepPopupData = $expiredTour->getCurrentStepPopupData();
				$showExpiredTour = $expiredTour->proceed();

				$this->arResult['tours']['expiredTasksDeadlineChange'] = [
					'show' => $showExpiredTour,
					'popupData' => $currentStepPopupData,
					'backgroundCheck' => !$showExpiredTour && $expiredTour->canPotentiallyProceed(),
					'counterToCheck' => $expiredTour->getNeededExpiredTasksCount(),
				];
			}
		}
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

		if ($filterOptions = $this->filter->getOptions())
		{
			$filterFields = $filterOptions->getFilter($this->filter->getFilters());
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

	protected function getSelect()
	{
		$columns = $this->grid->getVisibleColumns();

		if ($this->exportAs == false)
		{
			if ($this->needGroupBySubTasks())
			{
				$columns[] = 'PARENT_ID';
			}

			$preferredColumns = [
				'ID',
				'STATUS',
				'CREATED_BY',
				'RESPONSIBLE_ID',
				'AUDITORS',
				'ACCOMPLICES',
				'CHANGED_DATE',
				'ACTIVITY_DATE',
				'DEADLINE',
				'GROUP_ID',
				'PRIORITY',
				'ALLOW_CHANGE_DEADLINE',
				'ALLOW_TIME_TRACKING',
				'TIME_SPENT_IN_LOGS',
				'TIME_ESTIMATE',
				'VIEWED_DATE',
				'FAVORITE',
				'IS_MUTED',
				'IS_PINNED',
				'IS_PINNED_IN_GROUP'
			];

			$columns = array_merge($columns, $preferredColumns, array_keys($this->getUF()));
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
		$gridSort = [];
		$sortResult = [];

		if ($this->isGroupByProjectMode())
		{
			$sortResult['GROUP_ID'] = 'asc';
		}

		if ($this->canUsePin())
		{
			if (!$this->arParams['GROUP_ID'])
			{
				$sortResult['IS_PINNED'] = 'desc';
			}
			else
			{
				$sortResult['IS_PINNED_IN_GROUP'] = 'desc';
			}
		}

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		if ($request->get('SORTF') != null && in_array($request->get('SORTF'), Column::getFieldsForSorting()))
		{
			$sortResult[$request->get('SORTF')] = ($request->get('SORTD') ?: 'asc');

			$this->grid->getOptions()->setSorting($request->get('SORTF'), $sortResult[$request->get('SORTF')]);
			$this->grid->getOptions()->save();
		}
		else
		{
			$gridSort = $this->grid->getOptions()->GetSorting($this->getDefaultSorting())['sort'];
		}

		if (isset($gridSort['SORTING']))
		{
			$sortResult = ['SORTING' => 'asc'];
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
				if ($key === 'DEADLINE')
				{
					$value .= ',nulls';
				}
			}
		}

		return $sortResult;
	}

	/**
	 * @return bool
	 */
	private function canSortTasks(): bool
	{
		if ($this->arParams['SORT_FIELD'] !== 'SORTING')
		{
			return false;
		}

		$groupId = $this->arParams['GROUP_ID'];

		return (
			$groupId
				? SocialNetwork\Group::can($groupId, SocialNetwork\Group::ACTION_SORT_TASKS)
				: $this->isMyList()
		);
	}

	protected function mergeWithTags(array $items)
	{
		if (empty($items))
		{
			return array();
		}

		$res = TagTable::getList(array(
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

		$this->arParams['PROVIDER_PARAMETERS'] = [
			'MAKE_ACCESS_FILTER' => true,
		];

		$legacyFilter = $this->listParameters['filter'];

		/**
		 * Group by subtask should be ignored for fulltext search
		 * See #134428 for more information
		 */
		$listStateIsModified = false;
		if (array_key_exists('::SUBFILTER-FULL_SEARCH_INDEX', $legacyFilter))
		{
			unset($legacyFilter['ONLY_ROOT_TASKS']);

			$listState = \CTaskListState::getInstance($this->arParams['USER_ID']);
			if ($listState->isSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS))
			{
				$listStateIsModified = true;
				$listState->switchOffSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
			}
		}

		$getListParameters = [
			'select' => $this->getSelect(),
			'legacyFilter' => $legacyFilter,
			'order' => $this->getOrder(),
		];

		$page = $this->getPageNum();
		$this->savePageNumToStorage($page);

		if (
			$this->exportAs === false
			&& $this->getChildRowsAction === false
		)
		{
			$getListParameters['NAV_PARAMS'] = [
				'nPageSize' => $this->getPageSize(),
				'getPlusOne' => true,
				'bDescPageNumbering' => false,
				'NavShowAll' => false,
				'bShowAll' => false,
				'showAlways' => false,
				'SHOW_ALWAYS' => false,
				'iNumPage' => $page
			];
		}
		if (isset($this->listParameters['filter']['PARENT_ID']))
		{
			$getListParameters['NAV_PARAMS']['NavShowAll'] = true;
		}

		$parameters = $this->arParams['PROVIDER_PARAMETERS'];
		$parameters['ERRORS'] = $this->errors;
		$parameters['TARGET_USER_ID'] = $this->arParams['USER_ID'];

		if ($this->arParams['LAZY_LOAD'] && !$this->request->isAjaxRequest())
		{
			$mgrResult['DATA'] = [];
			$this->arResult['CURRENT_PAGE'] = 0;
		}
		else
		{
			$mgrResult = Manager\Task::getList(User::getId(), $getListParameters, $parameters);
			$this->arResult['CURRENT_PAGE'] = (int)$mgrResult['AUX']['OBJ_RES']->PAGEN;
		}

		$this->arResult['ENABLE_NEXT_PAGE'] = false;
		if (
			$this->exportAs === false
			&& (count($mgrResult['DATA']) > $this->getPageSize())
		)
		{
			$this->arResult['ENABLE_NEXT_PAGE'] = true;
			$keys = array_keys($mgrResult['DATA']);
			unset($mgrResult['DATA'][array_pop($keys)]);
		}

		if (array_key_exists('TAG', array_flip($getListParameters['select'])))
		{
			$mgrResult['DATA'] = $this->mergeWithTags($mgrResult['DATA']);
		}

		if (array_key_exists('FILTER_ID', $this->arParams))
		{
			$this->arParams['FILTER_FIELDS'] = (new Options($this->arParams['FILTER_ID']))->getFilter();
		}
		$this->arParams['GET_LIST_PARAMETERS'] = $getListParameters;
		$this->arResult['GET_LIST_PARAMS'] = $getListParameters;
		$this->arResult['LIST'] = $mgrResult['DATA'];
		$this->arResult['SUB_TASK_COUNTERS'] = $this->processSubTaskCounters();
		$this->arResult['STUB'] = $this->getStub();

		$this->arResult['LIST'] = self::setFilesCount($this->arResult['LIST']);
		$this->arResult['LIST'] = self::setCheckListCount($this->arResult['LIST']);
		$this->arResult['LIST'] = self::setGroupData($this->arResult['LIST']);
		$this->arResult['LIST'] = self::setUserData($this->arResult['LIST']);
		$this->arResult['PAGE_SIZES'] = $this->pageSizes;

		$this->validateCounters();

		if ($listStateIsModified)
		{
			$listState->switchOnSubmode(\CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS);
		}
	}

	private function getStub()
	{
		if ($this->arParams['LAZY_LOAD'] && !$this->request->isAjaxRequest())
		{
			return [];
		}

		if ($this->isUserFilterApplied())
		{
			return [
				'title' => Loc::getMessage('TASKS_GRID_STUB_NO_DATA_TITLE'),
				'description' => Loc::getMessage('TASKS_GRID_STUB_NO_DATA_DESCRIPTION'),
			];
		}

		if ($this->arParams['GROUP_ID'] > 0)
		{
			return '
				<div class="tasks-list-create">
					<div class="tasks-list-create-icon"></div>
					<div class="tasks-list-create-title">' . Loc::getMessage('TASKS_GRID_STUB_PROJECT_PROJECT_CREATED') . '</div>
					<div class="tasks-list-create-br"></div>
					<div class="tasks-list-create-title">' . Loc::getMessage('TASKS_GRID_STUB_PROJECT_CREATE_TASK') . '</div>
					<div class="tasks-list-create-subtitle">' . Loc::getMessage('TASKS_GRID_STUB_PROJECT_CREATE_TASK_SUBTITLE') . '</div>
				</div>
			';
		}

		return [
			'title' => Loc::getMessage('TASKS_GRID_STUB_TITLE'),
			'description' => Loc::getMessage('TASKS_GRID_STUB_DESCRIPTION'),
		];
	}

	private function isUserFilterApplied(): bool
	{
		if ($filterOptions = $this->filter->getOptions())
		{
			$currentPreset = $filterOptions->getCurrentFilterId();
			$isDefaultPreset = $filterOptions->getDefaultFilterId() === $currentPreset;
			$additionalFields = $filterOptions->getAdditionalPresetFields($currentPreset);
			$isSearchStringEmpty = $filterOptions->getSearchString() === '';

			return !$isSearchStringEmpty || !$isDefaultPreset || !empty($additionalFields);
		}

		return false;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\DB\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function validateCounters(): void
	{
		$userId = (int) $this->arParams['USER_ID'];
		if (!$this->isMyList())
		{
			return;
		}

		if (Counter::getGlobalLimit() !== null)
		{
			return;
		}

		$gridValue = count($this->arResult['LIST']);
		if (!$this->arResult['ENABLE_NEXT_PAGE'])
		{
			$gridValue = $this->arResult['GET_LIST_PARAMS']['NAV_PARAMS']['nPageSize'] * ($this->arResult['CURRENT_PAGE'] - 1) + $gridValue;
		}
		else
		{
			// @ToDo make inspect
		}

		if (
			$this->needGroupBySubTasks()
			&& $gridValue > 0
		)
		{
			return;
		}

		$filter = $this->filter->getOptions()->getFilter($this->filter->getFilters());
		if (
			array_key_exists('STATUS', $filter)
			&& is_array($filter['STATUS'])
			&& in_array(CTasks::STATE_IN_PROGRESS, $filter['STATUS'])
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

		$counter = Counter::getInstance($userId);
		$counterValue = $counter->get(Counter\CounterDictionary::COUNTER_EXPIRED, $groupId);

		if ($gridValue === $counterValue)
		{
			return;
		}

		if ($gridValue && $this->arResult['ENABLE_NEXT_PAGE'])
		{
			return;
		}

		if (Counter\Queue\Queue::isInQueue($userId))
		{
			return;
		}

		if (Counter\Event\EventTable::hasLostEvents())
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

	private static function setUserData(array $list)
	{
		$userIds = array_merge(array_column($list, 'CREATED_BY'), array_column($list, 'RESPONSIBLE_ID'));
		$userIds = array_unique($userIds);

		$select = [
			'ID',
			'PERSONAL_PHOTO',
			'LOGIN',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'TITLE',
			'EMAIL'
		];
		$users = User::getData($userIds, $select);

		foreach ($list as $id => $row)
		{
			$list[$id]['MEMBERS']['CREATED_BY'] = $users[$row['CREATED_BY']];
			$list[$id]['MEMBERS']['RESPONSIBLE_ID'] = $users[$row['RESPONSIBLE_ID']];
		}

		return $list;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	private static function canSeeCounters(int $userId): bool
	{
		if (
			$userId === (int)User::getId()
			|| User::isAdmin()
			|| CTasks::IsSubordinate($userId, (int)User::getId())
		)
		{
			return true;
		}

		return false;
	}

	private static function setGroupData(array $list)
	{
		$groupIds = array_unique(array_column($list, 'GROUP_ID'));

		if (count($groupIds) === 1 && $groupIds[0] == 0)
		{
			return $list;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return $list;
		}

		$query = new Query(\Bitrix\Socialnetwork\WorkgroupTable::getEntity());
		$query->setSelect(['ID', 'IMAGE_ID', 'NAME']);
		$query->setFilter(['ID' => $groupIds]);

		$res = $query->exec();

		$groupData = [];
		while ($row = $res->fetch())
		{
			$groupData[$row['ID']] = $row;
		}

		foreach ($list as $id => $row)
		{
			$list[$id]['GROUP_NAME'] = (isset($groupData[$row['GROUP_ID']])) ? $groupData[$row['GROUP_ID']]['NAME'] : '';
			$list[$id]['GROUP_IMAGE_ID'] = (isset($groupData[$row['GROUP_ID']])) ? $groupData[$row['GROUP_ID']]['IMAGE_ID'] : 0;
		}

		return $list;
	}

	/**
	 * @return int
	 */
	private function getPageNum(): int
	{
		if (array_key_exists('clear_nav', $_REQUEST) && $_REQUEST['clear_nav'] == 'Y')
		{
			return 1;
		}

		if(isset($this->arParams['PAGE_NUMBER']) || isset($_REQUEST['page']))
		{
			$pageNum = (int)(isset($this->arParams['PAGE_NUMBER']) ? $this->arParams['PAGE_NUMBER'] : $_REQUEST['page']);
			if($pageNum < 0)
			{
				//Backward mode
				$offset = -($pageNum + 1);
				$total = Manager\Task::getCount($this->listParameters['filter'], $this->arParams['PROVIDER_PARAMETERS']);
				$pageNum = (int)(ceil($total / $this->getPageSize())) - $offset;
				if($pageNum <= 0)
				{
					$pageNum = 1;
				}
			}
			return $pageNum;
		}

		return $this->getPageNumFromStorage() ?? 1;
	}

	private function getPageNumFromStorage(): int
	{
		$app = \Bitrix\Main\Application::getInstance();
		if (method_exists($app, 'getLocalSession'))
		{
			$localStorage = $app->getLocalSession(self::STORAGE_KEY);
			if (!isset($localStorage[$this->arParams['GRID_ID']]))
			{
				return 0;
			}
			return $localStorage[$this->arParams['GRID_ID']];
		}

		if (isset($_SESSION[self::STORAGE_KEY][$this->arParams['GRID_ID']]))
		{
			return $_SESSION[self::STORAGE_KEY][$this->arParams['GRID_ID']];
		}

		return 0;
	}

	private function savePageNumToStorage(int $page)
	{
		$app = \Bitrix\Main\Application::getInstance();
		if (method_exists($app, 'getLocalSession'))
		{
			$localStorage = $app->getLocalSession(self::STORAGE_KEY);
			$localStorage->set($this->arParams['GRID_ID'], $page);
		}
		else
		{
			$_SESSION[self::STORAGE_KEY][$this->arParams['GRID_ID']] = $page;
		}
	}

	private static function setFilesCount(array $list)
	{
		if (Loader::includeModule('disk'))
		{
			$cntIds = ConnectorTask::getFilesCount(array_keys($list));
			foreach ($cntIds as $taskId => $count)
			{
				$list[$taskId]['COUNT_FILES'] = $count;
			}
		}

		return $list;
	}

	private static function setCheckListCount(array $list)
	{
		$query = new Query(Bitrix\Tasks\Internals\Task\CheckListTable::getEntity());
		$query->setSelect(['TASK_ID', 'IS_COMPLETE', new ExpressionField('CNT', 'COUNT(TASK_ID)')]);
		$query->setFilter(['TASK_ID' => array_keys($list), ]);
		$query->setGroup(['TASK_ID', 'IS_COMPLETE']);
		$query->registerRuntimeField('', new ReferenceField(
			'IT',
			Bitrix\Tasks\Internals\Task\CheckListTreeTable::class,
			Join::on('this.ID', 'ref.CHILD_ID')->where('ref.LEVEL', 1),
			['join_type' => 'INNER']
		));

		$res = $query->exec();
		while ($row = $res->fetch())
		{
			$checkList =& $list[$row['TASK_ID']]['CHECK_LIST'];
			$checkList[$row['IS_COMPLETE'] == 'Y' ? 'COMPLETE' : 'WORK'] = $row['CNT'];
		}

		return $list;
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

	/**
	 * @param $taskId
	 * @param int $level
	 * @return array
	 */
	private function getSubTasks($taskId, int $level = 0): array
	{
		$subTasks = [];

		if (CTasks::getTaskSubTree($taskId))
		{
			$this->listParameters['filter']['PARENT_ID'] = $taskId;
			$getListParameters = [
				'select' => $this->getSelect(),
				'legacyFilter' => $this->listParameters['filter'],
				'order' => $this->getOrder(),
			];
			$level++;

			$mgrResult = Manager\Task::getList($this->arParams['USER_ID'], $getListParameters);
			if ($mgrResult['DATA'])
			{
				if (array_key_exists('TAG', array_flip($getListParameters['select'])))
				{
					$mgrResult['DATA'] = $this->mergeWithTags($mgrResult['DATA']);
				}

				foreach ($mgrResult['DATA'] as $item)
				{
					$item['__LEVEL'] = $level;
					$subTasks[] = $item;
					if ($sub = $this->getSubTasks($item['ID'], $level))
					{
						$subTasks = array_merge($subTasks, $sub);
					}
				}
			}
		}

		return $subTasks;
	}

	protected function display()
	{
		global $APPLICATION;

		if ($this->errors->checkNoFatals())
		{
			if ($this->exportAs)
			{
				$APPLICATION->RestartBuffer();

				if ($this->arResult['GROUP_BY_SUBTASK'])
				{
					$list = [];
					foreach ($this->arResult['LIST'] as $item)
					{
						$list[] = $item;
						if ($subTasks = $this->getSubTasks($item['ID']))
						{
							$list = array_merge($list, $subTasks);
						}
					}
					$this->arResult['LIST'] = $list;
				}

				$this->IncludeComponentTemplate('export_'.mb_strtolower($this->exportAs));
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
		}
	}
}