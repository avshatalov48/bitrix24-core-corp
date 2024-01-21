<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Integration\Intranet;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\Report\Internals\TaskTable;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Internals\RunTime;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksEmployeePlanComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $errorCollection;

	protected static function getPageSize()
	{
		return 15;
	}

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'getGridRegion' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function getGridRegionAction(array $filter = [], array $nav = [], array $parameters = ['GET_COUNT_TOTAL' => false])
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$res = UserTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $this->userId,
				'=IS_REAL_USER' => true,
			],
			'limit' => 1
		])->fetch();

		if (empty($res))
		{
			$this->addForbiddenError();
			return null;
		}

		if(Extranet\User::isExtranet($this->userId))
		{
			$this->addForbiddenError();
			return null;
		}


		$result = $this->getGridRegion($filter, $nav, $parameters);
		$this->errorCollection = $result->getErrors();

		$data = $result->getData();

		if (isset($data['DATA']['TASKS']) && is_array($data['DATA']['TASKS']))
		{
			foreach ($data['DATA']['TASKS'] as $k => $task)
			{
				if (is_a($task['START_DATE_PLAN'], \Bitrix\Tasks\Util\Type\DateTime::class))
				{
					$data['DATA']['TASKS'][$k]['START_DATE_PLAN'] = $task['START_DATE_PLAN']->toString();
				}
				if (is_a($task['END_DATE_PLAN'], \Bitrix\Tasks\Util\Type\DateTime::class))
				{
					$data['DATA']['TASKS'][$k]['END_DATE_PLAN'] = $task['END_DATE_PLAN']->toString();
				}
			}
		}

		return $data;
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function getGridRegion(array $filter = [], array $nav = [], array $parameters = ['GET_COUNT_TOTAL' => false])
	{
		$result = new Result();
		$errors = $result->getErrors();

		$args = static::prepareQueryParameters($filter);
		$filterErrors = $args['ERRORS'];

		$errors->load($filterErrors);

		if(!$errors->checkHasFatals())
		{
			if($filterErrors->filter(array('CODE' => 'INVALID_FILTER'))->isEmpty())
			{
				static::saveFilter($filter);
			}

			// apply nav to user list
			$limit = static::getPageSize();
			$page = intval($nav['PAGE']);

			$args['USER']['limit'] = $limit;
			$args['USER']['offset'] = $page > 0 ? ($page - 1) * $limit : 0;

			$region = static::getGridRegionData($args['USER'], $args['TASK']);
			$data = array(
				'DATA' => $region['DATA'],
				'PAGE' => $page,
				'PAGE_SIZE' => $limit,
			);

			if($parameters['GET_COUNT_TOTAL'])
			{
				$data['COUNT_TOTAL'] = static::getUserCount($args['USER']);
			}

			$result->setData($data);
			$errors->load($region['ERRORS']);
		}

		return $result;
	}

	protected static function getMaximumDateRange()
	{
		return 7776000; // 60*60*24*90 (90 days)
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		if(Extranet\User::isExtranet($arResult['USER_ID']))
		{
			$errors->add('ACCESS_DENIED', Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'));
		}

		return $errors->checkHasFatals();
	}

	protected static function checkIfToolAvailable(array &$arParams, array &$arResult, Collection $errors, array $auxParams): void
	{
		parent::checkIfToolAvailable($arParams, $arResult, $errors, $auxParams);

		if (!$arResult['IS_TOOL_AVAILABLE'])
		{
			return;
		}

		$arResult['IS_TOOL_AVAILABLE'] = (new Settings())->isToolAvailable(Settings::TOOLS['employee_plan']);
	}

	protected function getData()
	{
		$this->arResult['FILTER'] = $this->getFilter();
		$this->getFilterData();

		// do query
		$result = $this->getGridRegion($this->arResult['FILTER'], array('PAGE' => 1), array('GET_COUNT_TOTAL' => true));

		$this->errors->load($result->getErrors());

		$this->arResult['DATA']['REGION'] = $result->getData();

		$this->arResult['AUX_DATA']['TASK']['STATUS'] = CTaskItem::getStatusMap();
		$this->arResult['AUX_DATA']['COMPANY_WORKTIME'] = static::getCompanyWorkTime();

		$this->arResult['COMPONENT_DATA']['PAGE_SIZE'] = static::getPageSize();
		$this->arResult['COMPONENT_DATA']['MAXIMUM_DATE_RANGE'] = static::getMaximumDateRange();
	}

	protected function getFilterData()
	{
		$departments = Department::getCompanyStructure();
		$users = static::getDepartmentUser(/*$dep*/)->getData();

		// check if selected departments and users are really exist
		$filter =& $this->arResult['FILTER'];
		if(is_array($filter['MEMBER']['DEPARTMENT']))
		{
			foreach($filter['MEMBER']['DEPARTMENT'] as $k => $departmentId)
			{
				if(!array_key_exists($departmentId, $departments))
				{
					unset($filter['MEMBER']['DEPARTMENT'][$k]);
				}
			}
		}
		if(is_array($filter['MEMBER']['USER']))
		{
			foreach($filter['MEMBER']['USER'] as $k => $userId)
			{
				if(!array_key_exists($userId, $users))
				{
					unset($filter['MEMBER']['USER'][$k]);
				}
			}
		}

		$filter = $this->arResult['FILTER'];
		$filterDepartments = $filter['MEMBER']['DEPARTMENT'];
		$filterUsers = $filter['MEMBER']['USER'];

		$dep = 0;
		if(!empty($filterDepartments))
		{
			$dep = array_shift($filterDepartments); // get the first one
		}
		$user = 0;
		if(!empty($filterUsers))
		{
			$user = array_shift($filterUsers); // get the first one
		}

		$this->arResult['AUX_DATA']['FILTER'] = array(
			'DEPARTMENT' => array(
				'STRUCTURE' => array_values($departments),
				'USERS' => array_values($users),
			),
			'SELECTED' => array(
				'DEPARTMENT' => $dep,
				'USER' => $user,
			),
		);
	}

	protected function getReferenceData()
	{
		// get data about entities that are in filter
		$users = array();
		$groups = array();
		$departments = array();

		if(!empty($this->arResult['FILTER']['MEMBER']))
		{
			$mem = $this->arResult['FILTER']['MEMBER'];

			// users
			if(is_array($mem['USER']) && !empty($mem['USER']))
			{
				$users = User::getData($mem['USER']);
			}

			// departments
			if(is_array($mem['DEPARTMENT']) && !empty($mem['DEPARTMENT']))
			{
				$departments = Intranet\Department::getData($mem['DEPARTMENT']);
			}
		}

		$this->arResult['DATA']['USER'] = $users;
		$this->arResult['DATA']['GROUP'] = $groups;
		$this->arResult['DATA']['DEPARTMENT'] = $departments;
	}

	protected function getFilter()
	{
		$filter = $this->request->getQueryList();

		$filterCtrl = new TasksEmployeePlanComponentFilterStorage();

		if(isset($filter['FILTER_OWNER'])) // apply filter from query, but only if there is FILTER_OWNER
		{
			$filter = $filter->toArray();
			$owner = $filter['FILTER_OWNER'];
			$filter = $filterCtrl->check($filter);
			$filter['FILTER_OWNER'] = $owner;
		}
		else // or else get filter from db
		{
			//$filterCtrl->remove(); // tmp
			$filter = $filterCtrl->get();
			$filter['FILTER_OWNER'] = User::getId();
		}

		$filter['TASK']['DATE_RANGE'] = static::correctDateRange($filter['TASK']['DATE_RANGE'], $this->errors);

		return $filter;
	}

	protected static function saveFilter($filter)
	{
		if($filter['FILTER_OWNER'] == User::getId())
		{
			$filterCtrl = new TasksEmployeePlanComponentFilterStorage();
			$filterCtrl->set($filter);
		}
	}

	protected static function prepareQueryParametersTasks($filter, Collection $errors)
	{
		$tParams = array();

		$tParams['select'] = array('ID', 'TITLE', 'RESPONSIBLE_ID', 'CREATED_BY', 'ACCOMPLICE_ID' => 'M.USER_ID', 'START_DATE_PLAN', 'END_DATE_PLAN');

		if(!is_array($filter['TASK']))
		{
			$filter['TASK'] = array();
		}

		// date range filter
		$range = static::correctDateRange($filter['TASK']['DATE_RANGE'], $errors);
		$tParams['filter'][] = array(
			array(
				'LOGIC' => 'NOT',
				array(
					'LOGIC' => 'OR',
					// on interval:
					// ........... [......]
					// exclude the following tasks:
					// [.......]
					// (.......]
					array(
						array(
							'!=END_DATE_PLAN' => false,
							'<=END_DATE_PLAN' => $range['FROM']
						),
						array(
							'LOGIC' => 'OR',
							array('=START_DATE_PLAN' => false),
							array('<=START_DATE_PLAN' => $range['FROM'])
						)
					),
					// on interval:
					// ..[......]
					// exclude the following tasks:
					// ........... [.......]
					// ........... [.......)
					array(
						array(
							'LOGIC' => 'OR',
							array('=END_DATE_PLAN' => false),
							array('>=END_DATE_PLAN' => $range['TO'])
						),
						array(
							'!=START_DATE_PLAN' => false,
							'>=START_DATE_PLAN' => $range['TO']
						)
					),
				)
			),
		);

		// plan dates not empty
		$tParams['filter'][] = array(
			'!=START_DATE_PLAN' => false,
			'!=END_DATE_PLAN' => false,
		);

		// filter tasks by status
		if ($status = (int)($filter['TASK']['STATUS'] ?? null))
		{
			$tParams['filter']['=STATUS'] = $status;
		}

		return $tParams;
	}

	protected static function prepareQueryParametersUsers($filter, Collection $errors, array $params)
	{
		$uParams = array();

		// for users
		$uParams['runtime'] = array();
		$uParams['select'] = User::getPublicDataKeys();
		$uParams['order'] = array('NAME' => 'asc', 'LAST_NAME' => 'asc');
		$uParams['filter'] = array(
			'=ACTIVE' => 'Y',
			'!=EXTERNAL_AUTH_ID' => \Bitrix\Main\UserTable::getExternalUserTypes(), // not an artificial user
		);

		if($params['DEPARTMENT_UF_EXISTS'])
		{
			$constraint = Intranet\Internals\Runtime\UserDepartment::getUserDepartmentField(['REF_FIELD' => 'ID']);

			if(!empty($constraint['runtime'])) // else wont able to include runtime
			{
				$uParams = Bitrix\Tasks\Internals\RunTime::apply($uParams, [$constraint]);

				$uParams['select'] = array('DEPARTMENT_ID' => 'DEP.ID') + $uParams['select'];
				$uParams['order'] = array('DEP.LEFT_MARGIN' => 'asc') + $uParams['order'];
			}
			else
			{
				$errors->add('INTERNAL_ERROR', 'Was not able to include runtime constraint');
			}
		}
		else
		{
			$errors->add('NO_USER_FIELD', 'User field '.$params['DEPARTMENT_UF_CODE'].' is absent, filter will not work properly', Error::TYPE_WARNING);
		}

		// add member filter
		if (
			isset($filter['MEMBER'])
			&& is_array($filter['MEMBER'])
		)
		{
			$member = $filter['MEMBER'];
			$user = (isset($member['USER']) ? Type::normalizeArrayOfUInteger($member['USER']) : []);
			$department = (isset($member['DEPARTMENT']) ? Type::normalizeArrayOfUInteger($member['DEPARTMENT']) : []);

			$uMemberFilter = array();

			// logic is "and"
			// by user
			if(!empty($user))
			{
				$uMemberFilter['=ID'] = array_unique($member['USER']);
			}
			elseif($params['DEPARTMENT_UF_EXISTS'] && !empty($department)) // by department
			{
				$departments = array_map('intval', array_unique($member['DEPARTMENT']));
				if(!empty($departments))
				{
					// todo: refactor this, use LEFT\RIGHT MARGINS
					$subDeps = array();
					foreach($departments as $k => $v)
					{
						$subDeps[] = $v;
						$subDeps = array_merge($subDeps, Department::getSubIds($v, false, true)); // get all sub-departments for departments specified
					}

					$uParams['filter']['DEP.ID'] = array_unique($subDeps);
				}
			}

			if(count($uMemberFilter))
			{
				$uParams['filter'][] = $uMemberFilter;
			}
		}

		return $uParams;
	}

	protected static function prepareQueryParameters(array $filter = array())
	{
		$errors = new Collection();

		$departmentUFCode = Intranet\User::getDepartmentUFCode();
		$departmentUFExists = \Bitrix\Tasks\Util\Userfield\User::checkFieldExists($departmentUFCode);

		$tParams = static::prepareQueryParametersTasks($filter, $errors);
		$uParams = static::prepareQueryParametersUsers($filter, $errors, array(
			'DEPARTMENT_UF_CODE' => $departmentUFCode,
			'DEPARTMENT_UF_EXISTS' => $departmentUFExists
		));

		$result = array(
			'USER' => &$uParams,
			'TASK' => &$tParams,
			'ERRORS' => $errors
		);

		return $result;
	}

	/**
	 * Returns a data-set for a grid region specified by bounds
	 *
	 * @param array $userParameters User bound (vertical, user list)
	 * @param array $taskParameters Task bound (horizontal, timeline)
	 * @return array
	 */
	protected static function getGridRegionData(array $userParameters = [], array $taskParameters = [])
	{
		$result = [
			'DATA' => [],
			'ERRORS' => new Collection(),
		];

		$usersQuery = new Query(UserTable::getEntity());
		static::applyQueryParameters($usersQuery, $userParameters);

		$userIds = [];
		$users = [];
		$usersResult = $usersQuery->exec();
		while ($user = $usersResult->fetch())
		{
			$userIds[] = (int)$user['ID'];
			$users[] = $user;
		}
		$result['USER_DB_RESULT'] = $usersResult;
		$result['DATA']['USERS'] = $users;

		$tasks = [];
		if (!empty($userIds)) // some users match the user filter, take their tasks
		{
			$taskParameters['runtime']['M'] = [
				'data_type' => '\Bitrix\Tasks\MemberTable',
				'reference' => [
					'=this.ID' => 'ref.TASK_ID',
					'=ref.TYPE' => ['?', 'A'],
					'@ref.USER_ID' => new \Bitrix\Main\DB\SqlExpression(implode(', ', $userIds)),
				],
				'join_type' => 'left',
			];
			$taskParameters['select']['ACCOMPLICE_ID'] = 'M.USER_ID';
			$taskParameters['filter'][] = [
				'LOGIC' => 'OR',
				['@RESPONSIBLE_ID' => $userIds],
				['!M.TASK_ID' => false],
			];

			$tasksQuery = new Query(TaskTable::getEntity());
			static::applyQueryParameters($tasksQuery, $taskParameters);

			$taskIds = [];
			$tasksResult = $tasksQuery->exec();
			while ($task = $tasksResult->fetch())
			{
				$taskIds[(int)$task['ID']] = true;
				$tasks[] = $task;
			}

			(new \Bitrix\Tasks\Access\AccessCacheLoader())->preload(User::getId(), array_keys($taskIds));

			foreach ($tasks as &$task)
			{
				// need to find out if I can view task or not
				$canRead = TaskAccessController::can(
					User::getId(),
					ActionDictionary::ACTION_TASK_READ,
					$task['ID']
				);
				if (!$canRead)
				{
					$task['TITLE'] = ''; // unable to see TITLE
				}
				$task['ACTION']['READ'] = $canRead;
			}
			unset($task);
		}
		$result['DATA']['TASKS'] = $tasks;

		return $result;
	}

	protected static function getUserCount(array $qParams)
	{
		$qUser = new \Bitrix\Main\Entity\Query(UserTable::getEntity());

		$qUser = RunTime::apply($qUser, array(array_intersect_key($qParams, array('runtime' => true, 'filter' => true)))); // set filter and runtime
		$qUser = RunTime::apply($qUser, array(RunTime::getRecordCount(array('NAME' => 'COUNT'))));
		$qUser->setSelect(array('COUNT'));

		$res = $qUser->exec()->fetch();

		return intval($res['COUNT']);
	}

	///////////////////////////////////
	///////////////////////////////////
	///////////////////////////////////

	public static function getDepartmentUser(/*$id*/)
	{
		$result = new Result();

		$departmentUFCode = Intranet\User::getDepartmentUFCode();
		$departmentUFExists = \Bitrix\Tasks\Util\Userfield\User::checkFieldExists($departmentUFCode);

		if(!$departmentUFExists)
		{
			$result->getErrors()->add('NO_USER_FIELD', 'User field '.$departmentUFCode.' is absent');
		}
		else
		{
			$constraint = Intranet\Internals\Runtime\UserDepartment::getUserDepartmentField(['REF_FIELD' => 'ID']);

			if(!empty($constraint))
			{
				$users = array();
				$res = UserTable::getList(Runtime::apply(
					array(
						'filter' => array(
							'=ACTIVE' => 'Y', // user is active
							'!=EXTERNAL_AUTH_ID' => \Bitrix\Main\UserTable::getExternalUserTypes(), // not an artificial user
						),
						'select' => User::getPublicDataKeys() + ['DEPARTMENT_ID' => 'DEP.ID'],
						'order' => ['NAME', 'LAST_NAME', 'ID', 'DEP.DEPTH_LEVEL' => 'DESC', 'DEP.LEFT_MARGIN' => 'DESC'],
					),
					array(
						$constraint,
					)
				));
				while($item = $res->fetch())
				{
					$users[$item['ID']] = $item;
				}

				$result->setData($users);
			}
			else
			{
				$result->getErrors()->add('INTERNAL_ERROR', 'Was not able to include runtime constraint');
			}
		}

		return $result;
	}

	private static function applyQueryParameters($query, array $parameters)
	{
		if(is_array($parameters['runtime']))
		{
			foreach($parameters['runtime'] as $k => $v)
			{
				$isObject = is_subclass_of($v, '\Bitrix\Main\Entity\Field');

				if($isObject)
				{
					$v = clone $v;
				}

				$query->registerRuntimeField(
					$isObject ? '' : $k,
					$v
				);
			}
		}

		if (
			isset($parameters['filter'])
			&& is_array($parameters['filter'])
		)
		{
			$query->setFilter($parameters['filter']);
		}
		if (
			isset($parameters['order'])
			&& is_array($parameters['order'])
		)
		{
			$query->setOrder($parameters['order']);
		}
		if (
			isset($parameters['select'])
			&& is_array($parameters['select'])
		)
		{
			$query->setSelect($parameters['select']);
		}

		if (isset($parameters['limit']))
		{
			$query->setLimit($parameters['limit']);
		}
		if (isset($parameters['offset']))
		{
			$query->setOffset($parameters['offset']);
		}

		if (
			isset($parameters['group'])
			&& is_array($parameters['group'])
		)
		{
			$query->setGroup($parameters['group']);
			$query->countTotal();
		}
	}

	/**
	 * Checks datetime interval passed
	 *
	 * @param array $range
	 * @param Collection $errors
	 * @return array
	 */
	protected static function correctDateRange($range, Collection $errors)
	{
		if(!is_array($range) || empty($range))
		{
			$from = $to = '';
		}
		else
		{
			$from =     (string) $range['FROM'];
			$to =       (string) $range['TO'];
		}

		$limit = static::getMaximumDateRange();

		// local dates expected, in site format

		if($from != '' && $to != '')
		{
			$fromTs =   \Bitrix\Tasks\UI::parseDateTime($from);
			$toTs =     \Bitrix\Tasks\UI::parseDateTime($to);

			if($fromTs > $toTs)
			{
				$errors->add('INVALID_FILTER.DATE_RANGE', 'Invalid time range');
			}
			else
			{
				// check length
				if($toTs - $fromTs > $limit)
				{
					$errors->add('INVALID_FILTER.DATE_RANGE', Loc::getMessage('TASKS_TEP_DATE_RANGE_TOO_LONG'));
				}
			}
		}
		elseif($from != '' && $to == '')
		{
			// set $from + max length
			$to = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\UI::parseDateTime($from) + $limit);
		}
		elseif($from == '' && $to != '')
		{
			// set $to - max length
			$from = \Bitrix\Tasks\UI::formatDateTime(\Bitrix\Tasks\UI::parseDateTime($to) - $limit);
		}
		else
		{
			// set default
			// todo: we need a handy library to perform widely-used operations with dates in three formats:
			// todo: 1) timestamp(), 2) string site format, 3) \Bitrix\Main\Type\DateTime
			$now = strtotime(date("Y-m-d 00:00:00", User::getTime()));

			$oneThird = floor($limit / 3);
			$from = \Bitrix\Tasks\UI::formatDateTime($now - $oneThird);
			$to = \Bitrix\Tasks\UI::formatDateTime($now + $oneThird*2);
		}

		return array(
			'FROM' => $from, 'TO' => $to
		);
	}
}

/**
 * Class TasksEmployeePlanComponentFilterStorage
 *
 * @access private
 */
if(CModule::IncludeModule('tasks'))
{
	final class TasksEmployeePlanComponentFilterStorage extends \Bitrix\Tasks\Util\Type\ArrayOption
	{
		protected static function getFilterOptionName()
		{
			return 'tasks_component_tep_filter';
		}

		protected function getRules()
		{
			return array(
				'MEMBER' => array('VALUE' => array(
					'USER' => array(
						'VALUE' => 'unique integer[]',
						'DEFAULT' => array(),
						'INITIAL' => 'TasksEmployeePlanComponentFilterStorage::getInitialUser'
					),
					'DEPARTMENT' => array(
						'VALUE' => 'unique integer[]',
						'DEFAULT' => array(),
						'INITIAL' => 'TasksEmployeePlanComponentFilterStorage::getInitialDepartment'
					),
				), 'DEFAULT' => array()),
				'TASK' => array('VALUE' => array(
					'STATUS' => array('VALUE' => 'integer'),
					'DATE_RANGE' => array('VALUE' => array(
						'FROM' => array('VALUE' => '\Bitrix\Tasks\UI::checkDateTime'),
						'TO' => array('VALUE' => '\Bitrix\Tasks\UI::checkDateTime'),
					), 'DEFAULT' => array()),
				), 'DEFAULT' => array())
			);
		}

		public function set(array $value)
		{
			global $_SESSION;

			$value = static::check($value);

			if (
				!isset($_SESSION['TASKS'])
				|| !is_array($_SESSION['TASKS'])
			)
			{
				$_SESSION['TASKS'] = [];
			}
			if (
				!isset($_SESSION['TASKS']['OPTION'])
				|| !is_array($_SESSION['TASKS']['OPTION'])
			)
			{
				$_SESSION['TASKS']['OPTION'] = [];
			}

			// write checked values to the session
			$_SESSION['TASKS']['OPTION'][static::getFilterOptionName()] = array(
				'TASK' => array(
					'DATE_RANGE' => $value['TASK']['DATE_RANGE']
				)
			);
			unset($value['TASK']['DATE_RANGE']);

			User::setOption(static::getFilterOptionName(), Type::serializeArray($value));
		}

		public function get()
		{
			global $_SESSION;

			$value = Type::unSerializeArray($this->fetchOptionValue());

			// mix up with session
			$inSession = array();
			if (
				isset($_SESSION['TASKS']['OPTION'])
				&& is_array($_SESSION['TASKS']['OPTION'][static::getFilterOptionName()])
			)
			{
				$inSession = $_SESSION['TASKS']['OPTION'][static::getFilterOptionName()];
			}

			return static::check(
				array_merge_recursive($value, $inSession),
				!$this->checkOptionValueExists()
			);
		}

		public function remove()
		{
			global $_SESSION;

			unset($_SESSION['TASKS']['OPTION'][static::getFilterOptionName()]);

			parent::remove();
		}

		public static function getInitialUser()
		{
			if(!Intranet\User::isDirector() && !User::isAdmin())
			{
				return array(User::getId());
			}

			return array();
		}

		public static function getInitialDepartment()
		{
			if(Intranet\User::isDirector())
			{
				$sDeps = Intranet\Department::getSubordinateIds();
				if(is_array($sDeps))
				{
					return $sDeps;
				}
			}

			return array();
		}
	}
}