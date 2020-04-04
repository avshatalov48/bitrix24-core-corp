<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Internals\Counter;
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

	protected function checkParameters()
	{
		static::tryParseStringParameter($this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		static::tryParseStringParameter(
			$this->arParams['PATH_TO_USER_TASKS'],
			'/company/personal/user/#user_id#/tasks/'
		);

		static::tryParseIntegerParameter($this->arParams['FILTER_ID'], 'TASKS_MANAGE_GRID_ID');
		static::tryParseIntegerParameter($this->arParams['GRID_ID'], $this->arParams['FILTER_ID']);

		static::tryParseIntegerParameter($this->arParams['DEPARTMENT_ID'], (int)$_REQUEST['DEPARTMENT_ID']);

		return $this->errors->checkNoFatals();
	}

	private function updateManagerCounter()
	{
		$count = 0;

		$filter['ACTIVE'] = 'Y';
		$filter['UF_DEPARTMENT'] = array_keys($this->getDepartmentsTree());

		// SUMMARY COUNTERS
		$users = \Bitrix\Main\UserTable::getList(
			[
				'select' => [
					'ID'
				],
				'filter' => $filter
			]
		);

		if ($users = $users->fetchAll())
		{
			$counters = $this->getCounters(array_unique(array_column($users, 'ID')));
			foreach ($users as $user)
			{
				$counter = $counters[$user['ID']];
				$count += $counter['RESPONSIBLE']['NOTICE'] +
						  $counter['ORIGINATOR']['NOTICE'] +
						  $counter['AUDITOR']['NOTICE'] +
						  $counter['ACCOMPLICE']['NOTICE'];
			}
		}

		\CUserCounter::Set($this->arParams['USER_ID'], 'departments_counter', $count, '**');
	}

	protected function getData()
	{
		$this->arResult['DEPARTMENTS'] = $this->getDepartments();
		$this->arResult['GRID']['HEADERS'] = $this->getGridHeaders();
		$this->arResult['FILTER']['FIELDS'] = $this->getFilterFields();
		$this->arResult['FILTER']['PRESETS'] = $this->getFilterPresets();

		$this->updateManagerCounter();

		$this->arResult['GRID']['NAV'] = new \Bitrix\Main\UI\PageNavigation("department-more");
		$this->arResult['GRID']['NAV']->allowAllRecords(true)->setPageSize(25)->initFromUri();

		$filter['LOGIC'] = 'AND';
		$filter['ACTIVE'] = 'Y';
		$filter[] = $this->processFilter();


		if (!array_key_exists('UF_DEPARTMENT', $filter))
		{
			$filter['UF_DEPARTMENT'] = array_keys($this->getDepartmentsTree());
		}
		//		unset($filter['UF_DEPARTMENT']);

		$select = [
			'ID',
			'PERSONAL_PHOTO',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'LOGIN',
			'EMAIL',
			'TITLE',
			'UF_DEPARTMENT',
			//			'EFFECTIVE' => 'COUNTER.CNT',
			new \Bitrix\Main\Entity\ExpressionField('EFFECTIVE', 'IFNULL(%1$s, 100)', ['COUNTER.CNT'])
		];

		$users = \Bitrix\Main\UserTable::getList(
			[
				'select'      => $select,
				'filter'      => $filter,
				"count_total" => true,
				"offset"      => $this->arResult['GRID']['NAV']->getOffset(),
				"limit"       => $this->arResult['GRID']['NAV']->getLimit()
			]
		);

		$this->arResult['GRID']['NAV']->setRecordCount($users->getCount());
		$this->arResult['GRID']['DATA'] = $users = $users->fetchAll();

		if (!empty($users))
		{
			$this->arResult['COUNTERS'] = $this->getCounters(array_unique(array_column($users, 'ID')));

			// SUMMARY COUNTERS
			$users = \Bitrix\Main\UserTable::getList(
				[
					'select' => [
						'ID',
						new \Bitrix\Main\Entity\ExpressionField('EFFECTIVE', 'IFNULL(%1$s, 100)', ['COUNTER.CNT'])
					],
					'filter' => $filter
				]
			);

			if ($users = $users->fetchAll())
			{
				$count = 0;
				$effective = 0;
				$counters = $this->getCounters(array_unique(array_column($users, 'ID')));

				foreach ($users as $user)
				{
					$counter = $counters[$user['ID']];

					$this->arResult['SUMMARY']['RESPONSIBLE']['ALL'] += $counter['RESPONSIBLE']['ALL'];
					$this->arResult['SUMMARY']['RESPONSIBLE']['NOTICE'] += $counter['RESPONSIBLE']['NOTICE'];

					$this->arResult['SUMMARY']['ORIGINATOR']['ALL'] += $counter['ORIGINATOR']['ALL'];
					$this->arResult['SUMMARY']['ORIGINATOR']['NOTICE'] += $counter['ORIGINATOR']['NOTICE'];

					$this->arResult['SUMMARY']['AUDITOR']['ALL'] += $counter['AUDITOR']['ALL'];
					$this->arResult['SUMMARY']['AUDITOR']['NOTICE'] += $counter['AUDITOR']['NOTICE'];

					$this->arResult['SUMMARY']['ACCOMPLICE']['ALL'] += $counter['ACCOMPLICE']['ALL'];
					$this->arResult['SUMMARY']['ACCOMPLICE']['NOTICE'] += $counter['ACCOMPLICE']['NOTICE'];

					$effective += $user['EFFECTIVE'];

					$count += $counter['RESPONSIBLE']['NOTICE'] +
							  $counter['ORIGINATOR']['NOTICE'] +
							  $counter['AUDITOR']['NOTICE'] +
							  $counter['ACCOMPLICE']['NOTICE'];
				}

				$this->arResult['SUMMARY']['EFFECTIVE'] = $effective / count($users);
			}
		}
	}

	private function getCounters(array $userIds)
	{
		$list = [];

		$sql = '
			SELECT 
				USER_ID,
				SUM(MY_EXPIRED) /*+ SUM(MY_EXPIRED_SOON) + SUM(MY_NOT_VIEWED) + SUM(MY_WITHOUT_DEADLINE)*/ AS RESPONSIBLE,
				/*SUM(ORIGINATOR_WITHOUT_DEADLINE) +*/ SUM(ORIGINATOR_EXPIRED) + SUM(ORIGINATOR_WAIT_CTRL) AS ORIGINATOR,
				SUM(AUDITOR_EXPIRED) AS AUDITOR,
				SUM(ACCOMPLICES_EXPIRED) /*+ SUM(ACCOMPLICES_EXPIRED_SOON) + SUM(ACCOMPLICES_NOT_VIEWED)*/ AS ACCOMPLICE 
			FROM 
				b_tasks_counters
			WHERE 
				USER_ID IN('.join(',', $userIds).')
			GROUP BY 
				USER_ID
		';
		$res = \Bitrix\Main\Application::getConnection()->query($sql)->fetchAll();
		$notice = [];
		foreach ($res as $row)
		{
			$notice[$row['USER_ID']] = $row;
		}

		$sql = '
			SELECT 
				tm.USER_ID
				, tm.TYPE
				, COUNT(tm.TASK_ID) AS COUNT
			FROM 
				b_tasks_member AS tm
			JOIN 
				b_tasks AS t ON t.ID = tm.TASK_ID
			WHERE
				tm.USER_ID IN ('.join(',', $userIds).")
				AND t.STATUS <= 3
				AND tm.TYPE IN ('A', 'U')
				AND t.ZOMBIE = 'N'
			GROUP BY 
				tm.USER_ID
				, tm.TYPE 
		";

		$res = \Bitrix\Main\Application::getConnection()->query($sql)->fetchAll();
		$counters = [];
		foreach ($res as $row)
		{
			$counters[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		$sql = "
			SELECT 
				t.CREATED_BY AS USER_ID
				, 'O' AS TYPE
				, COUNT(t.ID) AS COUNT
			FROM 
				b_tasks AS t
			WHERE
				t.STATUS <= 3
				AND t.CREATED_BY IN (".join(',', $userIds).")
				AND t.CREATED_BY != t.RESPONSIBLE_ID
				AND t.ZOMBIE = 'N'
			GROUP BY 
				t.CREATED_BY
		";

		$res = \Bitrix\Main\Application::getConnection()->query($sql)->fetchAll();
		foreach ($res as $row)
		{
			$counters[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		$sql = "
			SELECT 
				t.RESPONSIBLE_ID AS USER_ID
				, 'R' AS TYPE
				, COUNT(t.ID) AS COUNT
			FROM 
				b_tasks AS t
			WHERE
				t.STATUS <= 3
				AND t.ZOMBIE = 'N'
				AND t.RESPONSIBLE_ID IN (".join(',', $userIds).")
			GROUP BY 
				t.RESPONSIBLE_ID
		";

		$res = \Bitrix\Main\Application::getConnection()->query($sql)->fetchAll();
		foreach ($res as $row)
		{
			$counters[$row['USER_ID']][$row['TYPE']] = $row['COUNT'];
		}

		foreach ($userIds as $userId)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_TASKS'] . '?apply_filter=Y',
				['user_id' => $userId]
			);
			$statuses = '&STATUS[]=2&STATUS[]=3';
			$roles = [
				'ORIGINATOR' => [
					'LETTER' => 'O',
					'ROLE' => Counter\Role::ORIGINATOR
				],
				'RESPONSIBLE' => [
					'LETTER' => 'R',
					'ROLE' => Counter\Role::RESPONSIBLE
				],
				'AUDITOR' => [
					'LETTER' => 'U',
					'ROLE' => Counter\Role::AUDITOR
				],
				'ACCOMPLICE' => [
					'LETTER' => 'A',
					'ROLE' => Counter\Role::ACCOMPLICE
				]
			];

			foreach ($roles as $roleName => $roleParameters)
			{
				$list[$userId][$roleName] = [
					'NOTICE' => (int)$notice[$userId][$roleName],
					'ALL' => (int)$counters[$userId][$roleParameters['LETTER']],
					'URL' => $url . '&ROLEID=' . $roleParameters['ROLE'] . $statuses
				];
			}
		}

		return $list;
	}

	private function getDepartmentsInternal()
	{
		static $list;

		if (!$list)
		{
			$list = \CIntranetUtils::GetStructure();
		}

		return $list['DATA'];
	}

	private function getDepartments()
	{
		$list = $this->getDepartmentsInternal();

		return $list;
	}

	private function getDepartmentsTree()
	{
		static $list = [];

		if (!$list)
		{
			$allDeps = \CIntranetUtils::GetStructure();

			$deps = \CIntranetUtils::GetSubordinateDepartments($this->arParams['USER_ID']);

			foreach ($deps as $depId)
			{
				$list[$depId] = $allDeps['DATA'][$depId]['NAME'];

				$subDeps = \CIntranetUtils::GetDeparmentsTree($depId, 0);

				if (empty($subDeps))
				{
					continue;
				}

				foreach ($subDeps[$depId] as $subDepId)
				{
					$list[$subDepId] = str_repeat('.', 2).$allDeps['DATA'][$subDepId]['NAME'];

					$subSubDeps = \CIntranetUtils::GetDeparmentsTree($subDepId, 0);
					if (array_key_exists($subDepId, $subSubDeps))
					{
						foreach ($subSubDeps[$subDepId] as $subSubDepId)
						{
							$list[$subSubDepId] = str_repeat('.', 4).$allDeps['DATA'][$subSubDepId]['NAME'];
						}
					}
				}
			}
		}

		return $list;
	}

	private function getFilterFields()
	{
		$departments = $this->getDepartmentsTree();

		return [
			'ID'            => [
				'id' => 'ID',
				'default' => true,
				'name' => Loc::getMessage('TASKS_MANAGE_USER'),
				'params' => ['multiple' => 'Y'],
				'type' => 'custom_entity',
				'selector' => [
					'TYPE' => 'user',
					'DATA' => [
						'ID' => 'user',
						'FIELD_ID' => 'ID'
					]
				]
			],
			'EFFECTIVE'     => [
				'id'      => 'EFFECTIVE',
				'name'    => Loc::getMessage('TASKS_MANAGE_EFFECTIVE'),
				'default' => true,
				'type'    => 'number'
			],
			'UF_DEPARTMENT' => [
				'id' => 'UF_DEPARTMENT',
				'default' => true,
				'name' => Loc::getMessage('TASKS_MANAGE_DEPARTMENT_ID'),
				'params' => ['multiple' => 'Y'],
				'type' => 'list',
				'items' => $departments
			]
		];
	}

	/**
	 * @return array
	 */
	private function getFilterPresets()
	{
		$list = [];
		$deps = \CIntranetUtils::GetSubordinateDepartments($this->arParams['USER_ID']);
		$allDeps = \CIntranetUtils::GetStructure();

		if ($deps)
		{
			foreach ($deps as $depId)
			{
				$list['filter_tasks_templates_effective_more'] = [
					'name'    => $allDeps['DATA'][$depId]['NAME'],
					'default' => false,
					'fields'  => [
						'UF_DEPARTMENT' => [$depId]
					]
				];
			}
		}

		return $list;
	}

	/**
	 * @return array
	 */
	private function getGridHeaders()
	{
		return [
			'NAME' => [
				'id' => 'NAME',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_TITLE'),
//				'sort' => 'ID',
//				'first_order' => 'desc',
				'default'=>true,
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
//				'sort' => 'EFFECTIVE',
//				'first_order' => 'desc',
				'default'=>true,
				'editable' => false
			],
			'RESPONSIBLE' => [
				'id' => 'RESPONSIBLE',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_RESPONSIBLE'),
//				'sort' => 'EFFECTIVE',
//				'first_order' => 'desc',
				'default'=>true,
				'editable' => false
			],
			'ORIGINATOR' => [
				'id' => 'ORIGINATOR',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_ORIGINATOR'),
//				'sort' => 'EFFECTIVE',
//				'first_order' => 'desc',
				'default'=>true,
				'editable' => false
			],
			'ACCOMPLICE' => [
				'id' => 'ACCOMPLICE',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_ACCOMPLICE'),
//				'sort' => 'EFFECTIVE',
//				'first_order' => 'desc',
				'default'=>true,
				'editable' => false
			],
			'AUDITOR' => [
				'id' => 'AUDITOR',
				'name' => GetMessage('TASKS_MANAGE_COLUMN_AUDITOR'),
//				'sort' => 'AUDITOR',
//				'first_order' => 'desc',
				'default'=>true,
				'editable' => false
			]
		];
	}

	private function getFilterFieldData($field, $default = null)
	{
		static $filterData;

		if (!$filterData)
		{
			$filterData = $this->getFilterOptions()->getFilter($this->getFilterFields());
		}

		return array_key_exists($field, $filterData) ? $filterData[$field] : $default;
	}

	private function processFilter()
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

	/**
	 * @return Filter\Options
	 */
	private function getFilterOptions()
	{
		static $instance = null;

		if (!$instance)
		{
			return new Filter\Options($this->arParams['FILTER_ID']);
		}

		return $instance;
	}
}