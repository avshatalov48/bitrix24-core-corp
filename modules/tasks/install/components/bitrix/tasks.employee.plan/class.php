<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Integration\Intranet;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\Report\Internals\TaskTable;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Internals\RunTime;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksEmployeePlanComponent extends TasksBaseComponent
{
	protected static function getPageSize()
	{
		return 15;
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

	protected function getData()
	{
		$this->arResult['FILTER'] = $this->getFilter();
		$this->getFilterData();

		// do query
		$result = static::getGridRegion($this->arResult['FILTER'], array('PAGE' => 1), array('GET_COUNT_TOTAL' => true));

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
			'!=ZOMBIE' => 'Y', // todo: remove zombie mechanism
		);

		// plan dates not empty
		$tParams['filter'][] = array(
			'!=START_DATE_PLAN' => false,
			'!=END_DATE_PLAN' => false,
		);

		// filter tasks by status
		$status = intval($filter['TASK']['STATUS']);
		if($status)
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
			'!=EXTERNAL_AUTH_ID' => User::getArtificialExternalAuthIds(), // not an artificial user
		);

		if($params['DEPARTMENT_UF_EXISTS'])
		{
			$constraint = Intranet\Internals\Runtime\UserDepartment::getUserPrimaryDepartmentField();
			if(!empty($constraint['runtime'])) // else wont able to include runtime
			{
				$uParams = Bitrix\Tasks\Internals\RunTime::apply($uParams, array(
					$constraint,
					Intranet\Internals\Runtime\Department::get(array(
						'REF_FIELD' => 'this.PD'
					)),
				));

				$uParams['filter']['!=PD.DEPARTMENT_ID'] = false; // not an extranet user
				$uParams['filter']['=DEP.ACTIVE'] = true;
				$uParams['select'] = array('DEPARTMENT_ID' => 'PD.DEPARTMENT_ID') + $uParams['select'];
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
		if(is_array($filter['MEMBER']))
		{
			$member = $filter['MEMBER'];
			$user = \Bitrix\Tasks\Util\Type::normalizeArrayOfUInteger($member['USER']);
			$department = \Bitrix\Tasks\Util\Type::normalizeArrayOfUInteger($member['DEPARTMENT']);

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

					$uParams['filter']['PD.DEPARTMENT_ID'] = array_unique($subDeps);
				}
			}

			if(count($uMemberFilter))
			{
				$uParams['filter'][] = $uMemberFilter;
			}
		}

		$uParams['group'] = $uParams['select']; // distinct

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
	protected static function getGridRegionData(array $userParameters = array(), array $taskParameters = array())
	{
		$result = array(
			'DATA' => array(),
			'USER_DB_RESULT' => null,
			'ERRORS' => new Collection()
		);

		$qUser = new \Bitrix\Main\Entity\Query(UserTable::getEntity());
		$qTask = new \Bitrix\Main\Entity\Query(TaskTable::getEntity());

		static::applyQueryParameters($qUser, $userParameters);

		$userRes = $qUser->exec();
		$users = array();
		$ids = array();
		while($item = $userRes->fetch())
		{
			$users[] = $item;
			$ids[] = intval($item['ID']);
		}
		$result['USER_DB_RESULT'] = $userRes;

		$tasks = array();
		if(!empty($ids)) // some users match the user filter, take their tasks
		{
			$taskParameters['runtime']['M'] = array(
				'data_type' => '\Bitrix\Tasks\MemberTable',
				'reference' => array(
					'=this.ID' => 'ref.TASK_ID',
					'=ref.TYPE' => array('?', 'A'),
					'@ref.USER_ID' => new \Bitrix\Main\DB\SqlExpression(implode(', ', $ids)),
				),
				'join_type' => 'left'
			);
			$taskParameters['select']['ACCOMPLICE_ID'] = 'M.USER_ID';
			$taskParameters['filter'][] = array(
				'LOGIC' => 'OR',
				array('@RESPONSIBLE_ID' => $ids),
				array('!M.TASK_ID' => false)
			);

			static::applyQueryParameters($qTask, $taskParameters);

			$myEmployees = array();
			$isSuperUser = User::isSuper();
			if(!$isSuperUser)
			{
				$myEmployees = array_flip(\Bitrix\Tasks\Integration\Intranet\User::getSubordinateSubDepartments());
			}
			$myEmployees[User::getId()] = true;

			$taskRes = $qTask->exec();
			while($item = $taskRes->fetch())
			{
				// need to find out if i can view task or not
				$canRead = $isSuperUser ||
					(isset($myEmployees[$item['RESPONSIBLE_ID']])
					|| isset($myEmployees[$item['CREATED_BY']])
					|| isset($myEmployees[$item['ACCOMPLICE_ID']]));

				$item['ACTION']['READ'] = $canRead;
				if(!$canRead)
				{
					$item['TITLE'] = ''; // unable to see TITLE
				}

				$tasks[] = $item;
			}
		}

		$result['DATA'] = array('USERS' => $users, 'TASKS' => $tasks);

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
			$constraint = Intranet\Internals\Runtime\UserDepartment::getUserPrimaryDepartmentField();

			if(!empty($constraint))
			{
				$users = array();
				$res = UserTable::getList(Runtime::apply(array(
					'filter' => array(
						'=ACTIVE' => 'Y', // user is active
						'!PD.DEPARTMENT_ID' => false, // not an extranet user
						'=DEP.ACTIVE' => true, // department is active
						'!=EXTERNAL_AUTH_ID' => User::getArtificialExternalAuthIds(), // not an artificial user
					),
					'select' => User::getPublicDataKeys() + array('DEPARTMENT_ID' => 'PD.DEPARTMENT_ID'),
					'order' => array('NAME' => 'asc', 'LAST_NAME' => 'asc'),
				), array(
					$constraint,
					Intranet\Internals\Runtime\Department::get(array(
						'REF_FIELD' => 'this.PD'
					)),
				)));
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

	public static function getGridRegion(array $filter = array(), array $nav = array(), array $parameters = array('GET_COUNT_TOTAL' => false))
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

	public static function getAllowedMethods()
	{
		return array(
			'getGridRegion',
			//'getDepartmentUser',
		);
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

		if(is_array($parameters['filter']))
		{
			$query->setFilter($parameters['filter']);
		}
		if(is_array($parameters['order']))
		{
			$query->setOrder($parameters['order']);
		}
		if(is_array($parameters['select']))
		{
			$query->setSelect($parameters['select']);
		}

		if(isset($parameters['limit']))
		{
			$query->setLimit($parameters['limit']);
		}
		if(isset($parameters['offset']))
		{
			$query->setOffset($parameters['offset']);
		}

		if(is_array($parameters['group']))
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

			if(!is_array($_SESSION['TASKS']))
			{
				$_SESSION['TASKS'] = array();
			}
			if(!is_array($_SESSION['TASKS']['OPTION']))
			{
				$_SESSION['TASKS']['OPTION'] = array();
			}

			// write checked values to the session
			$_SESSION['TASKS']['OPTION'][static::getFilterOptionName()] = array(
				'TASK' => array(
					'DATE_RANGE' => $value['TASK']['DATE_RANGE']
				)
			);
			unset($value['TASK']['DATE_RANGE']);

			User::setOption(static::getFilterOptionName(), \Bitrix\Tasks\Util\Type::serializeArray($value));
		}

		public function get()
		{
			global $_SESSION;

			$value = \Bitrix\Tasks\Util\Type::unSerializeArray($this->fetchOptionValue());

			// mix up with session
			$inSession = array();
			if(is_array($_SESSION['TASKS']) && is_array($_SESSION['TASKS']['OPTION'][static::getFilterOptionName()]))
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