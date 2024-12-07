<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksDepartmentsOverviewComponent extends TasksBaseComponent
{
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		if (!Loader::includeModule('intranet'))
		{
			$errors->add('INTRANET_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_INTRANET_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		$currentUser = User::getId();
		$viewedUser = $arParams['USER_ID'];

		$isAccessible = $currentUser == $viewedUser;

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage("TASKS_COMMON_ACCESS_DENIED"));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkIfToolAvailable(array &$arParams, array &$arResult, Collection $errors, array $auxParams): void
	{
		parent::checkIfToolAvailable($arParams, $arResult, $errors, $auxParams);

		if (!$arResult['IS_TOOL_AVAILABLE'])
		{
			return;
		}

		$arResult['IS_TOOL_AVAILABLE'] = (new Settings())->isToolAvailable(Settings::TOOLS['departments']);
	}

	protected function checkParameters()
	{
		static::tryParseStringParameter($this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		static::tryParseStringParameter($this->arParams['PATH_TO_USER_TASKS'], '/company/personal/user/#user_id#/tasks/');

		static::tryParseIntegerParameter($this->arParams['FILTER_ID'], 'TASKS_MANAGE_GRID_ID');
		static::tryParseIntegerParameter($this->arParams['GRID_ID'], $this->arParams['FILTER_ID']);

		static::tryParseIntegerParameter($this->arParams['DEPARTMENT_ID'], (int)($_REQUEST['DEPARTMENT_ID'] ?? null));

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$this->arResult['DEPARTMENTS'] = $this->getDepartments();
		$this->arResult['GRID']['HEADERS'] = $this->getGridHeaders();
		$this->arResult['FILTER']['FIELDS'] = $this->getFilterFields();
		$this->arResult['FILTER']['PRESETS'] = $this->getFilterPresets();
		$this->arResult['GRID']['NAV'] = new PageNavigation('department-more');
		$this->arResult['GRID']['NAV']->allowAllRecords(true)->setPageSize(25)->initFromUri();

		$counters = $this->getUsersCounters();
		$this->updateManagerCounter($counters);

		$users = [];

		$taskSuperVisorExceeded = !Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_SUPERVISOR_VIEW);

		if (!$taskSuperVisorExceeded)
		{
			$usersResult = $this->getUsersResultWithNavigation();
			$users = $usersResult->fetchAll();

			$this->arResult['GRID']['NAV']->setRecordCount($usersResult->getCount());
		}

		$this->arResult['GRID']['DATA'] = $users;
		$this->arResult['TASK_LIMIT_EXCEEDED'] = $taskSuperVisorExceeded;
		$this->arResult['SUMMARY'] = [
			'RESPONSIBLE' => [
				'ALL' => 0,
				'NOTICE' => 0,
			],
			'ORIGINATOR' => [
				'ALL' => 0,
				'NOTICE' => 0,
			],
			'AUDITOR' => [
				'ALL' => 0,
				'NOTICE' => 0,
			],
			'ACCOMPLICE' => [
				'ALL' => 0,
				'NOTICE' => 0,
			],
			'EFFECTIVE' => 0,
		];

		if (!empty($users))
		{
			foreach ($users as $user)
			{
				$userId = $user['ID'];
				$this->arResult['COUNTERS'][$userId] = $counters[$userId];
			}

			if ($users = $this->getAllUsersResult()->fetchAll())
			{
				$effective = 0;

				foreach ($users as $user)
				{
					$userId = $user['ID'];
					$counter = $counters[$userId];

					$this->arResult['SUMMARY']['RESPONSIBLE']['ALL'] += $counter['RESPONSIBLE']['ALL'];
					$this->arResult['SUMMARY']['RESPONSIBLE']['NOTICE'] += $counter['RESPONSIBLE']['NOTICE'];

					$this->arResult['SUMMARY']['ORIGINATOR']['ALL'] += $counter['ORIGINATOR']['ALL'];
					$this->arResult['SUMMARY']['ORIGINATOR']['NOTICE'] += $counter['ORIGINATOR']['NOTICE'];

					$this->arResult['SUMMARY']['AUDITOR']['ALL'] += $counter['AUDITOR']['ALL'];
					$this->arResult['SUMMARY']['AUDITOR']['NOTICE'] += $counter['AUDITOR']['NOTICE'];

					$this->arResult['SUMMARY']['ACCOMPLICE']['ALL'] += $counter['ACCOMPLICE']['ALL'];
					$this->arResult['SUMMARY']['ACCOMPLICE']['NOTICE'] += $counter['ACCOMPLICE']['NOTICE'];

					$effective += $user['EFFECTIVE'];
				}

				$this->arResult['SUMMARY']['EFFECTIVE'] = $effective / count($users);
			}
		}
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getUsersCounters(): array
	{
		$users = UserTable::getList([
			'select' => ['ID'],
			'filter' => [
				'ACTIVE' => 'Y',
				'UF_DEPARTMENT' => array_keys($this->getDepartmentsTree()),
			],
		])->fetchAll();

		return ($users ? $this->getCounters(array_unique(array_column($users, 'ID'))) : []);
	}

	/**
	 * @param array $userIds
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 */
	private function getCounters(array $userIds): array
	{
		$list = [];

		$tasksByRoles = $this->getUserTasksByRoles($userIds);
		$tasksToNotice = $this->getUserTasksToNotice($userIds);

		foreach ($userIds as $userId)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_TASKS'].'?apply_filter=Y',
				['user_id' => $userId]
			);
			$roles = [
				'ORIGINATOR' => [
					'LETTER' => 'O',
					'ROLE' => Counter\Role::ORIGINATOR,
				],
				'RESPONSIBLE' => [
					'LETTER' => 'R',
					'ROLE' => Counter\Role::RESPONSIBLE,
				],
				'AUDITOR' => [
					'LETTER' => 'U',
					'ROLE' => Counter\Role::AUDITOR,
				],
				'ACCOMPLICE' => [
					'LETTER' => 'A',
					'ROLE' => Counter\Role::ACCOMPLICE,
				],
			];

			foreach ($roles as $roleName => $roleParameters)
			{
				$list[$userId][$roleName] = [
					'NOTICE' => (int)($tasksToNotice[$userId][$roleName] ?? null),
					'ALL' => (int)($tasksByRoles[$userId][$roleParameters['LETTER']] ?? null),
					'URL' => "{$url}&ROLEID={$roleParameters['ROLE']}&STATUS[]=2&STATUS[]=3",
				];
			}
		}

		return $list;
	}

	/**
	 * @param array $userIds
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 */
	private function getUserTasksToNotice(array $userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$query = Counter\CounterTable::query();
		$query
			->setSelect(['USER_ID', 'TYPE'])
			->addSelect($query::expr()->sum('VALUE'), 'CNT')
			->whereIn('USER_ID', $userIds)
			->addGroup('USER_ID')
			->addGroup('TYPE');

		$res = $query->fetchAll();

		$tasksToNotice = [];
		foreach ($res as $row)
		{
			switch ($row['TYPE'])
			{
				case Counter\CounterDictionary::COUNTER_MY_EXPIRED:
					$tasksToNotice[$row['USER_ID']]['RESPONSIBLE'] = $row['CNT'];
					break;
				case Counter\CounterDictionary::COUNTER_ORIGINATOR_EXPIRED:
					$tasksToNotice[$row['USER_ID']]['ORIGINATOR'] = $row['CNT'];
					break;
				case Counter\CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED:
					$tasksToNotice[$row['USER_ID']]['ACCOMPLICE'] = $row['CNT'];
					break;
				case Counter\CounterDictionary::COUNTER_AUDITOR_EXPIRED:
					$tasksToNotice[$row['USER_ID']]['AUDITOR'] = $row['CNT'];
					break;
				default:
					break;
			}
		}

		return $tasksToNotice;
	}

	/**
	 * @param array $userIds
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 */
	private function getUserTasksByRoles(array $userIds): array
	{
		$tasksByRoles = [];

		$connection = Application::getConnection();
		$statuses = implode(',', [Status::PENDING, Status::IN_PROGRESS]);
		$preparedUserIds = implode(',', $userIds);

		$res = $connection->query("
			SELECT
				T.CREATED_BY AS USER_ID,
				'O' AS TYPE,
				COUNT(T.ID) AS COUNT
			FROM b_tasks AS T
			WHERE
				T.STATUS IN ({$statuses})
				AND T.CREATED_BY IN ({$preparedUserIds})
				AND T.CREATED_BY != T.RESPONSIBLE_ID
			GROUP BY T.CREATED_BY
		")->fetchAll();
		foreach ($res as $row)
		{
			$tasksByRoles[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		$res = $connection->query("
			SELECT
				T.RESPONSIBLE_ID AS USER_ID,
				'R' AS TYPE,
				COUNT(T.ID) AS COUNT
			FROM b_tasks AS T
			WHERE
				T.STATUS IN ({$statuses})
				AND T.RESPONSIBLE_ID IN ({$preparedUserIds})
			GROUP BY T.RESPONSIBLE_ID
		")->fetchAll();
		foreach ($res as $row)
		{
			$tasksByRoles[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		$res = $connection->query("
			SELECT
				TM.USER_ID,
				TM.TYPE,
				COUNT(TM.TASK_ID) AS COUNT
			FROM b_tasks_member AS TM
			JOIN b_tasks AS T ON T.ID = TM.TASK_ID
			WHERE
				T.STATUS IN ({$statuses})
				AND TM.USER_ID IN ({$preparedUserIds})
				AND TM.TYPE IN ('A', 'U')
			GROUP BY TM.USER_ID, TM.TYPE 
		")->fetchAll();
		foreach ($res as $row)
		{
			$tasksByRoles[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		return $tasksByRoles;
	}

	/**
	 * @param array $counters
	 */
	private function updateManagerCounter(array $counters): void
	{
		$count = 0;
		foreach ($counters as $counter)
		{
			$count +=
				$counter['RESPONSIBLE']['NOTICE']
				+ $counter['ORIGINATOR']['NOTICE']
				+ $counter['AUDITOR']['NOTICE']
				+ $counter['ACCOMPLICE']['NOTICE'];
		}

		CUserCounter::Set($this->arParams['USER_ID'], 'departments_counter', $count, '**');
	}

	/**
	 * @return array
	 */
	private function getManageFilter(): array
	{
		$filter['LOGIC'] = 'AND';
		$filter['ACTIVE'] = 'Y';
		$filter[] = $this->processFilter();

		if (!array_key_exists('UF_DEPARTMENT', $filter))
		{
			$filter['UF_DEPARTMENT'] = array_keys($this->getDepartmentsTree());
		}

		return $filter;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getUsersResultWithNavigation(): Result
	{
		$helper = Application::getConnection()->getSqlHelper();
		return UserTable::getList([
			'select' => [
				'ID',
				'PERSONAL_PHOTO',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'LOGIN',
				'EMAIL',
				'TITLE',
				'UF_DEPARTMENT',
				new ExpressionField('EFFECTIVE', $helper->getIsNullFunction('%1$s', 100), ['COUNTER.CNT']),
			],
			'filter' => $this->getManageFilter(),
			'count_total' => true,
			'offset' => $this->arResult['GRID']['NAV']->getOffset(),
			'limit' => $this->arResult['GRID']['NAV']->getLimit(),
		]);
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getAllUsersResult(): Result
	{
		$helper = Application::getConnection()->getSqlHelper();
		return UserTable::getList([
			'select' => [
				'ID',
				new ExpressionField('EFFECTIVE', $helper->getIsNullFunction('%1$s', 100), ['COUNTER.CNT']),
			],
			'filter' => $this->getManageFilter(),
		]);
	}

	/**
	 * @return array
	 */
	private function getDepartments(): array
	{
		static $list;

		if (!$list)
		{
			$list = CIntranetUtils::GetStructure();
		}

		return $list['DATA'];
	}

	/**
	 * @return array
	 */
	private function getGridHeaders(): array
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_TITLE'),
				'default' => true,
				'editable' => false
			],
			'DEPARTMENTS' => [
				'id' => 'DEPARTMENTS',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_DEPARTMENTS'),
				'default' => true,
				'editable' => false
			],
			'EFFECTIVE' => [
				'id' => 'EFFECTIVE',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_EFFECTIVE'),
				'default' => true,
				'editable' => false
			],
			'RESPONSIBLE' => [
				'id' => 'RESPONSIBLE',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_RESPONSIBLE'),
				'default' => true,
				'editable' => false
			],
			'ORIGINATOR' => [
				'id' => 'ORIGINATOR',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_ORIGINATOR'),
				'default' => true,
				'editable' => false
			],
			'ACCOMPLICE' => [
				'id' => 'ACCOMPLICE',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_ACCOMPLICE'),
				'default' => true,
				'editable' => false
			],
			'AUDITOR' => [
				'id' => 'AUDITOR',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_AUDITOR'),
				'default' => true,
				'editable' => false
			]
		];
	}

	/**
	 * @return array
	 */
	private function getDepartmentsTree(): array
	{
		static $list = [];

		if (!empty($list))
		{
			return $list;
		}

		$userDepartments = CIntranetUtils::GetUserDepartments($this->arParams['USER_ID']);
		$departmentsIds = [];
		foreach ($userDepartments as $departmentId)
		{
			if ((int)CIntranetUtils::GetDepartmentManagerID($departmentId) === (int)$this->arParams['USER_ID'])
			{
				$departmentsIds = array_merge($departmentsIds, CIntranetUtils::GetIBlockSectionChildren($departmentId));
			}
		}

		$departmentsIds = array_unique($departmentsIds);

		$list = array_combine($departmentsIds, CIntranetUtils::GetDepartmentsData($departmentsIds));

		return $list;
	}

	/**
	 * @return array
	 */
	private function getFilterFields(): array
	{
		return [
			'ID' => [
				'id' => 'ID',
				'default' => true,
				'name' => Loc::getMessage('TASKS_MANAGE_USER'),
				'params' => ['multiple' => 'Y'],
				'type' => 'custom_entity',
				'selector' => [
					'TYPE' => 'user',
					'DATA' => [
						'ID' => 'user',
						'FIELD_ID' => 'ID',
					],
				],
			],
			'EFFECTIVE' => [
				'id' => 'EFFECTIVE',
				'name' => Loc::getMessage('TASKS_MANAGE_EFFECTIVE'),
				'default' => true,
				'type' => 'number',
			],
			'UF_DEPARTMENT' => [
				'id' => 'UF_DEPARTMENT',
				'default' => true,
				'name' => Loc::getMessage('TASKS_MANAGE_DEPARTMENT_ID'),
				'params' => ['multiple' => 'Y'],
				'type' => 'list',
				'items' => $this->getDepartmentsTree(),
			]
		];
	}

	/**
	 * @return array
	 */
	private function getFilterPresets(): array
	{
		$list = [];
		$deps = CIntranetUtils::GetSubordinateDepartments($this->arParams['USER_ID']);
		$allDeps = CIntranetUtils::GetStructure();

		if ($deps)
		{
			foreach ($deps as $depId)
			{
				$list['filter_tasks_templates_effective_more'] = [
					'name' => $allDeps['DATA'][$depId]['NAME'],
					'default' => false,
					'fields'  => [
						'UF_DEPARTMENT' => [$depId],
					],
				];
			}
		}

		return $list;
	}

	/**
	 * @return Options
	 */
	private function getFilterOptions(): Options
	{
		static $instance = null;

		if (!$instance)
		{
			return new Options($this->arParams['FILTER_ID']);
		}

		return $instance;
	}

	/**
	 * @param $field
	 * @param null $default
	 * @return mixed|null
	 */
	private function getFilterFieldData($field, $default = null)
	{
		static $filterData;

		if (!$filterData)
		{
			$filterData = $this->getFilterOptions()->getFilter($this->getFilterFields());
		}

		return array_key_exists($field, $filterData) ? $filterData[$field] : $default;
	}

	/**
	 * @return array
	 */
	private function processFilter(): array
	{
		static $filter = [];

		if (empty($filter))
		{
			if ($this->getFilterFieldData('FIND'))
			{
				$filter['LOGIC'] = 'OR';
				$filter['*%NAME'] = $this->getFilterFieldData('FIND');
				$filter['*%SECOND_NAME'] = $this->getFilterFieldData('FIND');
				$filter['*%LAST_NAME'] = $this->getFilterFieldData('FIND');
				$filter['*%LOGIN'] = $this->getFilterFieldData('FIND');
			}

			if ($this->getFilterFieldData('FILTER_APPLIED', false) != true)
			{
				return $filter;
			}

			foreach ($this->getFilterFields() as $item)
			{
				switch ($item['type'])
				{
					default:
						$field = $this->getFilterFieldData($item['id']);
						if ($field)
						{
							if (is_numeric($field) && $item['id'] != 'TITLE')
							{
								$filter[$item['id']] = $field;
							}
							else
							{
								$filter['%'.$item['id']] = $field;
							}
						}
						break;
					case 'number':
						if ($this->getFilterFieldData($item['id'].'_from'))
						{
							$filter['>'.$item['id']] = $this->getFilterFieldData($item['id'].'_from');
						}
						if ($this->getFilterFieldData($item['id'].'_to'))
						{
							$filter['<'.$item['id']] = $this->getFilterFieldData($item['id'].'_to');
						}

						if (array_key_exists('>'.$item['id'], $filter) &&
							array_key_exists('<'.$item['id'], $filter) &&
							$filter['>'.$item['id']] == $filter['<'.$item['id']])
						{
							$filter[$item['id']] = $filter['>'.$item['id']];
							unset($filter['>'.$item['id']], $filter['<'.$item['id']]);
						}
						break;

					case 'custom_entity':
					case 'list':
						if ($this->getFilterFieldData($item['id']))
						{
							$filter[$item['id']] = $this->getFilterFieldData($item['id']);
						}
						break;
				}
			}
		}

		return $filter;
	}
}