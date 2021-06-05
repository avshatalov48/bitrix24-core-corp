<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Util\Type\DateTime;

class CTasksDepartmentsOverviewComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(GetMessage('TASKS_TITLE_TASKS'));

		if (!CModule::IncludeModule('tasks'))
		{
			ShowError(GetMessage('TASKS_MODULE_NOT_FOUND'));

			return 0;
		}

		if (!CModule::IncludeModule('intranet'))
		{
			return 0;
		}

		$this->arResult['DEPARTMENTS'] = array();

		$this->processParams();        // preparw arResult

		if (!($this->arResult['LOGGED_IN_USER'] >= 1))
		{
			return 0;
		}

		$nameTemplate = $this->arResult['NAME_TEMPLATE'];

		$startFromDepartments = $this->getInitDepartmentsIds();

		$arDepartmentsData = CIntranetUtils::GetDepartmentsData($startFromDepartments);

		if ((!is_array($arDepartmentsData)) || empty($arDepartmentsData))
		{
			$this->IncludeComponentTemplate();

			return 0;
		}

		$allUsersIds = array();
		$arSubDepartmentsUsers = array();
		foreach ($arDepartmentsData as $departmentId => $departmentName)
		{
			$departmentHead = CIntranetUtils::GetDepartmentManagerID($departmentId);

			$arSubDepartmentsIds = CIntranetUtils::getSubDepartments($departmentId);

			$this->arResult['DEPARTMENTS'][$departmentId] = array(
				'~TITLE' => $departmentName,
				'TITLE' => htmlspecialcharsbx($departmentName),
				'HEAD_USER_ID' => $departmentHead,
				'RESPONSIBLES_TOTAL_TASKS' => 0,
				'RESPONSIBLES_NOTICED_TASKS' => 0,
				'ACCOMPLICES_TOTAL_TASKS' => 0,
				'ACCOMPLICES_NOTICED_TASKS' => 0,
				'ORIGINATORS_TOTAL_TASKS' => 0,
				'ORIGINATORS_NOTICED_TASKS' => 0,
				'AUDITORS_TOTAL_TASKS' => 0,
				'AUDITORS_NOTICED_TASKS' => 0,
				'USERS' => array(),
				'SUBDEPARTMENTS' => array()
			);

			$rsUsers = \Bitrix\Tasks\Integration\Intranet\User::getByDepartments(
				array($departmentId),
				array(
					'ID',
					'PERSONAL_PHOTO',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
					'LOGIN',
					'WORK_POSITION'
				)
			);

			$arUsers = array();
			$arDepartmentUsersIds = array();
			while ($arUser = $rsUsers->getNext())
			{
				$arUser['USER_IN_SUBDEPS'] = false;
				$arUsers[] = $arUser;
				$arDepartmentUsersIds[] = (int)$arUser['ID'];
			}

			if ($departmentHead > 0)
			{
				$arImmediateEmployees = \Bitrix\Tasks\Integration\Intranet\User::getSubordinate(
					$departmentHead,
					array($departmentId)    // $arAllowedDepartments
				);

				if (is_array($arImmediateEmployees) && !empty($arImmediateEmployees))
				{
					// Remove immediate manager's employees in subdeps, if they are already in current department
					$arImmediateEmployees = array_diff($arImmediateEmployees, $arDepartmentUsersIds);

					if (!empty($arImmediateEmployees))
					{
						$rsUsers = CUser::GetList(
							'ID',
							'ASC',
							array(
								'ACTIVE' => 'Y',
								'ID' => implode('|', array_unique($arImmediateEmployees))
							),
							array(
								'SELECT' => array('UF_DEPARTMENT'),
								'FIELDS' => array(
									'ID',
									'PERSONAL_PHOTO',
									'NAME',
									'LAST_NAME',
									'SECOND_NAME',
									'LOGIN',
									'WORK_POSITION'
								)
							)
						);

						while ($arUser = $rsUsers->getNext())
						{
							$arUser['USER_IN_SUBDEPS'] = true;
							$arUsers[] = $arUser;
						}
					}
				}
			}

			foreach ($arUsers as $arUser)
			{
				$userId = (int)$arUser['ID'];
				$allUsersIds[] = $userId;

				$userPhoto = false;
				if ($arUser['PERSONAL_PHOTO'] > 0)
				{
					$userPhoto = CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT);
				}

				$tasksHref = CComponentEngine::MakePathFromTemplate(
					$this->arResult['PATH_TO_USER_TASKS'],
					array('user_id' => $userId)
				);

				$this->arResult['DEPARTMENTS'][$departmentId]['USERS'][] = array(
					'ID' => $userId,
					'PHOTO' => $userPhoto,
					'DEPARTMENT_HEAD' => ($departmentHead == $userId) ? 'Y' : 'N',
					'USER_IN_SUBDEPS' => ($arUser['USER_IN_SUBDEPS'] ? 'Y' : 'N'),
					'FORMATTED_NAME' => CUser::FormatName(
						$nameTemplate,
						array(
							'NAME' => $arUser['~NAME'],
							'LAST_NAME' => $arUser['~LAST_NAME'],
							'SECOND_NAME' => $arUser['~SECOND_NAME'],
							'LOGIN' => $arUser['~LOGIN']
						),
						true,    // $bUseLogin
						true    // $bHtmlSpecialChars
					),
					'WORK_POSITION' => $arUser['WORK_POSITION'],
					'~WORK_POSITION' => $arUser['~WORK_POSITION'],
					'NAME' => $arUser['NAME'],
					'LAST_NAME' => $arUser['LAST_NAME'],
					'SECOND_NAME' => $arUser['SECOND_NAME'],
					'~NAME' => $arUser['~NAME'],
					'~LAST_NAME' => $arUser['~LAST_NAME'],
					'~SECOND_NAME' => $arUser['~SECOND_NAME'],
					'HREF' => CComponentEngine::MakePathFromTemplate(
						$this->arResult['PATH_TO_USER'],
						array('user_id' => $userId)
					),
					'RESPONSIBLES_TOTAL_TASKS' => 0,
					'RESPONSIBLES_NOTICED_TASKS' => 0,
					'ACCOMPLICES_TOTAL_TASKS' => 0,
					'ACCOMPLICES_NOTICED_TASKS' => 0,
					'ORIGINATORS_TOTAL_TASKS' => 0,
					'ORIGINATORS_NOTICED_TASKS' => 0,
					'AUDITORS_TOTAL_TASKS' => 0,
					'AUDITORS_NOTICED_TASKS' => 0,
					'RESPONSIBLES_TOTAL_HREF' => $tasksHref.'?F_CANCEL=Y&F_STATE=sR400',
					'RESPONSIBLES_NOTICED_HREF' => null,
					'ACCOMPLICES_TOTAL_HREF' => $tasksHref.'?F_CANCEL=Y&F_STATE=sR800',
					'ACCOMPLICES_NOTICED_HREF' => null,
					'ORIGINATORS_TOTAL_HREF' => $tasksHref.'?F_CANCEL=Y&F_STATE=sRg00',
					'ORIGINATORS_NOTICED_HREF' => null,
					'AUDITORS_TOTAL_HREF' => $tasksHref.'?F_CANCEL=Y&F_STATE=sRc00',
					'AUDITORS_NOTICED_HREF' => null,
					'EFFECTIVE_HREF' => $tasksHref.'effective/'
				);
			}

			if (is_array($arSubDepartmentsIds))
			{
				$arSubDepartmentsData = CIntranetUtils::GetDepartmentsData($arSubDepartmentsIds);

				foreach ($arSubDepartmentsIds as $subDepartmentId)
				{
					$title = '';
					if (array_key_exists($subDepartmentId, $arSubDepartmentsData))
					{
						$title = $arSubDepartmentsData[$subDepartmentId];
					}

					$this->arResult['DEPARTMENTS'][$departmentId]['SUBDEPARTMENTS'][$subDepartmentId] = array(
						'ID' => $subDepartmentId,
						'~TITLE' => $title,
						'TITLE' => htmlspecialcharsbx($title),
						'COUNTERS' => array(
							'RESPONSIBLES_TOTAL_TASKS' => 0,
							'RESPONSIBLES_NOTICED_TASKS' => 0,
							'ACCOMPLICES_TOTAL_TASKS' => 0,
							'ACCOMPLICES_NOTICED_TASKS' => 0,
							'ORIGINATORS_TOTAL_TASKS' => 0,
							'ORIGINATORS_NOTICED_TASKS' => 0,
							'AUDITORS_TOTAL_TASKS' => 0,
							'AUDITORS_NOTICED_TASKS' => 0,
							'EFFECTIVE' => 0,
						),
						'HREF' => '?DEP_ID='.(int)$subDepartmentId
					);

					$arSubDepartmentsUsers[$subDepartmentId] = array();
				}

				$rsUsers = CIntranetUtils::GetDepartmentEmployees(
					$arSubDepartmentsIds,
					$bRecursive = false,
					$bSkipSelf = false,
					$onlyActive = 'Y',
					array('ID', 'UF_DEPARTMENT')
				);

				while ($arUser = $rsUsers->fetch())
				{
					if (is_array($arUser['UF_DEPARTMENT']))
					{
						$userId = (int)$arUser['ID'];
						$allUsersIds[] = $userId;

						foreach ($arUser['UF_DEPARTMENT'] as $subDepartmentId)
						{
							if ($subDepartmentId > 0)
							{
								$arSubDepartmentsUsers[$subDepartmentId][] = $userId;
							}
						}
					}
				}
			}

			usort(
				$this->arResult['DEPARTMENTS'][$departmentId]['USERS'],
				function($a, $b) {
					if ($a['USER_IN_SUBDEPS'] !== $b['USER_IN_SUBDEPS'])
					{
						if ($a['USER_IN_SUBDEPS'] === 'N')
						{
							return (-1);
						}
						else
						{
							return (1);
						}
					}

					if ($a['DEPARTMENT_HEAD'] !== $b['DEPARTMENT_HEAD'])
					{
						if ($a['DEPARTMENT_HEAD'] === 'Y')
						{
							return (-1);
						}
						else
						{
							return (1);
						}
					}

					return strcmp($a['FORMATTED_NAME'], $b['FORMATTED_NAME']);
				}
			);
		}

		$arCounters = self::getCounts($allUsersIds);

		foreach ($startFromDepartments as $departmentId)
		{
			foreach ($this->arResult['DEPARTMENTS'][$departmentId]['USERS'] as &$userData)
			{
				$arCounter = $arCounters[$userData['ID']];

				$userData['RESPONSIBLES_TOTAL_TASKS'] = $arCounter['RESPONSIBLES_TOTAL_TASKS'];
				$userData['RESPONSIBLES_NOTICED_TASKS'] = $arCounter['RESPONSIBLES_NOTICED_TASKS'];
				$userData['ACCOMPLICES_TOTAL_TASKS'] = $arCounter['ACCOMPLICES_TOTAL_TASKS'];
				$userData['ACCOMPLICES_NOTICED_TASKS'] = $arCounter['ACCOMPLICES_NOTICED_TASKS'];
				$userData['ORIGINATORS_TOTAL_TASKS'] = $arCounter['ORIGINATORS_TOTAL_TASKS'];
				$userData['ORIGINATORS_NOTICED_TASKS'] = $arCounter['ORIGINATORS_NOTICED_TASKS'];
				$userData['AUDITORS_TOTAL_TASKS'] = $arCounter['AUDITORS_TOTAL_TASKS'];
				$userData['AUDITORS_NOTICED_TASKS'] = $arCounter['AUDITORS_NOTICED_TASKS'];
				$userData['EFFECTIVE'] = $arCounter['EFFECTIVE'];

				if ($userData['USER_IN_SUBDEPS'] === 'N')
				{
					$this->arResult['DEPARTMENTS'][$departmentId]['RESPONSIBLES_TOTAL_TASKS'] += $arCounter['RESPONSIBLES_TOTAL_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['RESPONSIBLES_NOTICED_TASKS'] += $arCounter['RESPONSIBLES_NOTICED_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['ACCOMPLICES_TOTAL_TASKS'] += $arCounter['ACCOMPLICES_TOTAL_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['ACCOMPLICES_NOTICED_TASKS'] += $arCounter['ACCOMPLICES_NOTICED_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['ORIGINATORS_TOTAL_TASKS'] += $arCounter['ORIGINATORS_TOTAL_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['ORIGINATORS_NOTICED_TASKS'] += $arCounter['ORIGINATORS_NOTICED_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['AUDITORS_TOTAL_TASKS'] += $arCounter['AUDITORS_TOTAL_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['AUDITORS_NOTICED_TASKS'] += $arCounter['AUDITORS_NOTICED_TASKS'];
					$this->arResult['DEPARTMENTS'][$departmentId]['EFFECTIVE'] += $arCounter['EFFECTIVE'];
				}
			}
			unset($userData);

			foreach ($this->arResult['DEPARTMENTS'][$departmentId]['SUBDEPARTMENTS'] as $subDepartmentId => &$subDepData)
			{
				foreach ($arSubDepartmentsUsers[$subDepartmentId] as $userId)
				{
					$arCounter = $arCounters[$userId];

					$subDepData['COUNTERS']['RESPONSIBLES_TOTAL_TASKS'] += $arCounter['RESPONSIBLES_TOTAL_TASKS'];
					$subDepData['COUNTERS']['RESPONSIBLES_NOTICED_TASKS'] += $arCounter['RESPONSIBLES_NOTICED_TASKS'];
					$subDepData['COUNTERS']['ACCOMPLICES_TOTAL_TASKS'] += $arCounter['ACCOMPLICES_TOTAL_TASKS'];
					$subDepData['COUNTERS']['ACCOMPLICES_NOTICED_TASKS'] += $arCounter['ACCOMPLICES_NOTICED_TASKS'];
					$subDepData['COUNTERS']['ORIGINATORS_TOTAL_TASKS'] += $arCounter['ORIGINATORS_TOTAL_TASKS'];
					$subDepData['COUNTERS']['ORIGINATORS_NOTICED_TASKS'] += $arCounter['ORIGINATORS_NOTICED_TASKS'];
					$subDepData['COUNTERS']['AUDITORS_TOTAL_TASKS'] += $arCounter['AUDITORS_TOTAL_TASKS'];
					$subDepData['COUNTERS']['AUDITORS_NOTICED_TASKS'] += $arCounter['AUDITORS_NOTICED_TASKS'];

					$subDepData['COUNTERS']['EFFECTIVE'] += $arCounter['EFFECTIVE'];
				}
			}
			unset($subDepData);
		}

		$this->IncludeComponentTemplate();
	}

	private function processParams()
	{
		$USERID = \Bitrix\Tasks\Util\User::getId();

		if ($USERID)
		{
			$this->arResult['LOGGED_IN_USER'] = $USERID;
		}
		else
		{
			$this->arResult['LOGGED_IN_USER'] = false;
		}

		if (!array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $this->arParams))
		{
			$this->arResult["PATH_TO_COMPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
		}
		else
		{
			$this->arResult["PATH_TO_COMPANY_DEPARTMENT"] = $this->arParams['PATH_TO_CONPANY_DEPARTMENT'];
		}

		if (isset($this->arParams['NAME_TEMPLATE']))
		{
			$this->arResult['NAME_TEMPLATE'] = $this->arParams['NAME_TEMPLATE'];
		}
		else
		{
			$this->arResult['NAME_TEMPLATE'] = CSite::GetNameFormat(false);
		}

		$this->arResult['PATH_TO_USER'] = $this->arParams['PATH_TO_USER'];
		$this->arResult['PATH_TO_USER_TASKS'] = $this->arParams['PATH_TO_USER_TASKS'];
	}

	private function getInitDepartmentsIds()
	{
		// Start from given department or from user-managed
		if (isset($_GET['DEP_ID']))
		{
			$startFromDepartmentsDraft = array((int)$_GET['DEP_ID']);
		}
		else
		{
			// Departments where given user is head
			$startFromDepartmentsDraft = array_unique(
				array_filter(
					array_map(
						'intval',
						\Bitrix\Tasks\Integration\Intranet\Department::getSubordinateIds(
							$this->arParams['USER_ID']
						)
					)
				)
			);
		}

		if (\Bitrix\Tasks\Util\User::isSuper())
		{
			// access to any departments
			$startFromDepartments = $startFromDepartmentsDraft;
		}
		else    // Filter unaccessible departments
		{
			$arAllAccessibleDepartments = array_unique(
				array_filter(
					array_map(
						'intval',
						\Bitrix\Tasks\Integration\Intranet\Department::getSubordinateIds(
							$this->arResult['LOGGED_IN_USER'],
							true
						)
					)
				)
			);

			$startFromDepartments = array();
			foreach ($startFromDepartmentsDraft as $departmentId)
			{
				if (in_array($departmentId, $arAllAccessibleDepartments, true))
				{
					$startFromDepartments[] = $departmentId;
				}
			}
		}

		return ($startFromDepartments);
	}

	private static function getCounts($arUsersIds)
	{
		$arUsersIds = array_unique(array_filter($arUsersIds));

		$arCounters = array();

		$currentDate = new DateTime();

		$dateFrom = DateTime::createFromTimestamp(
			strtotime($currentDate->format('01.m.Y 00:00:01'))
		);

		$dateTo = DateTime::createFromTimestamp(
			strtotime($currentDate->format('t.m.Y 23:59:59'))
		);

		foreach ($arUsersIds as $userId)
		{
			$arCounters[$userId] = array();

			$counter = Counter::getInstance($userId);

			$responsiblesNoticedTasks = $counter->get(Counter\CounterDictionary::COUNTER_MY);
			$accomplicesNoticedTasks = $counter->get(Counter\CounterDictionary::COUNTER_ACCOMPLICES);
			$originatorsNoticedTasks = $counter->get(Counter\CounterDictionary::COUNTER_ORIGINATOR);
			$auditorsNoticedTasks = $counter->get(Counter\CounterDictionary::COUNTER_AUDITOR);
			$effective = CUserCounter::GetValue(
				$userId,
				'tasks_effective'
			);

			if ($responsiblesNoticedTasks < 0)
			{
				$responsiblesNoticedTasks = 0;
			}

			if ($accomplicesNoticedTasks < 0)
			{
				$accomplicesNoticedTasks = 0;
			}

			if ($originatorsNoticedTasks < 0)
			{
				$originatorsNoticedTasks = 0;
			}

			if ($auditorsNoticedTasks < 0)
			{
				$auditorsNoticedTasks = 0;
			}

			$arCounters[$userId]['RESPONSIBLES_TOTAL_TASKS'] = 0;
			$arCounters[$userId]['RESPONSIBLES_NOTICED_TASKS'] = $responsiblesNoticedTasks;
			$arCounters[$userId]['ACCOMPLICES_TOTAL_TASKS'] = 0;
			$arCounters[$userId]['ACCOMPLICES_NOTICED_TASKS'] = $accomplicesNoticedTasks;
			$arCounters[$userId]['ORIGINATORS_TOTAL_TASKS'] = 0;
			$arCounters[$userId]['ORIGINATORS_NOTICED_TASKS'] = $originatorsNoticedTasks;
			$arCounters[$userId]['AUDITORS_TOTAL_TASKS'] = 0;
			$arCounters[$userId]['AUDITORS_NOTICED_TASKS'] = $auditorsNoticedTasks;
			$arCounters[$userId]['EFFECTIVE'] = $effective;
		}

		// Get RESPONSIBLES_TOTAL_TASKS counters
		$arFilterMy = CTaskListCtrl::getFilterFor(
			$arUsersIds,
			CTaskListState::VIEW_ROLE_RESPONSIBLE,
			CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
		);

		$rs = CTasks::GetCount(
			$arFilterMy,
			array(
				'bSkipUserFields' => true,
				'bSkipExtraTables' => true,
				'bSkipJoinTblViewed' => true
			),
			array('RESPONSIBLE_ID')        // group by
		);

		while ($ar = $rs->fetch())
		{
			$userId = (int)$ar['RESPONSIBLE_ID'];

			if ($userId)
			{
				$arCounters[$userId]['RESPONSIBLES_TOTAL_TASKS'] = (int)$ar['CNT'];
			}
		}

		// Get ORIGINATORS_TOTAL_TASKS counters
		$arFilterOriginator = CTaskListCtrl::getFilterFor(
			$arUsersIds,
			CTaskListState::VIEW_ROLE_ORIGINATOR,
			CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
		);

		$rs = CTasks::GetCount(
			$arFilterOriginator,
			array(
				'bSkipUserFields' => true,
				'bSkipExtraTables' => true,
				'bSkipJoinTblViewed' => true
			),
			array('CREATED_BY')        // group by
		);

		while ($ar = $rs->fetch())
		{
			$userId = (int)$ar['CREATED_BY'];

			if ($userId)
			{
				$arCounters[$userId]['ORIGINATORS_TOTAL_TASKS'] = (int)$ar['CNT'];
			}
		}

		// Get ACCOMPLICES_TOTAL_TASKS counters
		$arFilterAccomplice = CTaskListCtrl::getFilterFor(
			$arUsersIds,
			CTaskListState::VIEW_ROLE_ACCOMPLICE,
			CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
		);

		$rs = CTasks::GetCount(
			$arFilterAccomplice,
			array(
				'bSkipUserFields' => true,
				'bSkipExtraTables' => true,
				'bSkipJoinTblViewed' => true
			),
			array('ACCOMPLICE')        // group by
		);

		while ($ar = $rs->fetch())
		{
			$userId = (int)$ar['ACCOMPLICE'];

			if ($userId)
			{
				$arCounters[$userId]['ACCOMPLICES_TOTAL_TASKS'] = (int)$ar['CNT'];
			}
		}

		// Get AUDITORS_TOTAL_TASKS counters
		$arFilterAuditor = CTaskListCtrl::getFilterFor(
			$arUsersIds,
			CTaskListState::VIEW_ROLE_AUDITOR,
			CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS
		);

		$rs = CTasks::GetCount(
			$arFilterAuditor,
			array(
				'bSkipUserFields' => true,
				'bSkipExtraTables' => true,
				'bSkipJoinTblViewed' => true
			),
			array('AUDITOR')        // group by
		);

		while ($ar = $rs->fetch())
		{
			$userId = (int)$ar['AUDITOR'];

			if ($userId)
			{
				$arCounters[$userId]['AUDITORS_TOTAL_TASKS'] = (int)$ar['CNT'];
			}
		}

		return ($arCounters);
	}
}
