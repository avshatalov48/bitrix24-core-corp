<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// DEPRECATED
if (!\Bitrix\Main\Loader::includeModule("socialnetwork"))
{
	return;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Tasks\Internals\Counter;

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$arDefaultValues = array(
	'GROUP_ID'               => 0,
	'SHOW_TASK_LIST_MODES'   => 'Y',
	'SHOW_HELP_ICON'         => 'Y',
	'SHOW_SEARCH_FIELD'      => 'Y',
	'SHOW_TEMPLATES_TOOLBAR' => 'Y',
	'SHOW_QUICK_TASK_ADD'    => 'Y',
	'SHOW_ADD_TASK_BUTTON'   => 'Y',
	'SHOW_SECTIONS_BAR'      => 'N',
	'SHOW_FILTER_BAR'        => 'N',
	'SHOW_COUNTERS_BAR'      => 'N',
	'SHOW_SECTION_MANAGE'    => 'A',
	'MARK_ACTIVE_ROLE'       => 'N',
	'MARK_SECTION_MANAGE'    => 'N',
	'MARK_SECTION_PROJECTS'  => 'N',
	'MARK_SECTION_REPORTS'   => 'N',
	'MARK_SECTION_EMPLOYEE_PLAN' => 'N',
	'SECTION_URL_PREFIX'     => '',
	'PATH_TO_DEPARTMENTS'    => null,
	'PATH_TO_REPORTS'        => null
);

if ( ! isset($arParams['NAME_TEMPLATE']) )
	$arParams['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

$loggedInUserId = (int) $USER->getId();

$isAccessToCounters = ($arParams['USER_ID'] == $loggedInUserId)
	|| $USER->isAdmin()
	|| CTasksTools::IsPortalB24Admin()
	|| CTasks::IsSubordinate($arParams['USER_ID'], $loggedInUserId);

if ($arParams["GROUP_ID"] > 0)
	$arParams['SHOW_SECTION_COUNTERS'] = 'N';

if ( ! $isAccessToCounters )
	$arParams['SHOW_SECTION_COUNTERS'] = 'N';

// Set default values for omitted parameters
foreach ($arDefaultValues as $paramName => $paramDefaultValue)
{
	if ( ! array_key_exists($paramName, $arParams) )
		$arParams[$paramName] = $paramDefaultValue;
}

if ( ! $arParams['PATH_TO_USER_TASKS_TASK'] )
	$arParams['PATH_TO_USER_TASKS_TASK'] = $arParams['SECTION_URL_PREFIX'] . 'task/#action#/#task_id#/';

if ( ! $arParams['PATH_TO_REPORTS'] )
	$arParams['PATH_TO_REPORTS'] = $arParams['SECTION_URL_PREFIX'] . 'report/';

if ( ! $arParams['PATH_TO_DEPARTMENTS'] )
	$arParams['PATH_TO_DEPARTMENTS'] = $arParams['SECTION_URL_PREFIX'] . 'departments/';

if ( ! $arParams['PATH_TO_EMPLOYEE_PLAN'] )
	$arParams['PATH_TO_EMPLOYEE_PLAN'] = $arParams['SECTION_URL_PREFIX'] . 'employee/plan/';

$arResult['SHOW_SECTION_MANAGE'] = $arParams['SHOW_SECTION_MANAGE'];

$arResult['F_SEARCH'] = null;

if ($fTitle = tasksGetFilter("F_TITLE") <> '')
	$arResult['F_SEARCH'] = $fTitle;
elseif (intval($fID = tasksGetFilter("F_META::ID_OR_NAME")) > 0)
	$arResult['F_SEARCH'] = $fID;

if (
	($arParams['SHOW_SECTIONS_BAR'] === 'Y')
	|| ($arParams['SHOW_FILTER_BAR'] === 'Y')
	|| ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
)
{
	// Show this section ONLY if given user is head of department
	// and logged in user is admin or given user or manager of given user
	if ($arParams['SHOW_SECTION_MANAGE'] === 'A')
	{
		$arResult['SHOW_SECTION_MANAGE'] = 'N';

		if ($isAccessToCounters)
		{
			if(\Bitrix\Tasks\Integration\Intranet\User::isDirector($arParams['USER_ID']))
			{
				$arResult['SHOW_SECTION_MANAGE'] = 'Y';
			}
		}
	}

	if (
		($arResult['SHOW_SECTION_MANAGE'] === 'Y')
		&& ($arParams['GROUP_ID'] > 0)
	)
	{
		$arResult['SHOW_SECTION_MANAGE'] = 'N';
	}

	if ($arResult['SHOW_SECTION_MANAGE'] === 'Y')
	{
		$arResult['SECTION_MANAGE_COUNTER'] = 0;

		if ($arEmployees = \Bitrix\Tasks\Integration\Intranet\User::getSubordinate($arParams['USER_ID']))
		{
			foreach ($arEmployees as $employeeId)
			{
				$employeeId = (int) $employeeId;

				$arResult['SECTION_MANAGE_COUNTER'] += Counter::getInstance($employeeId)
															  ->get(Counter\CounterDictionary::COUNTER_MEMBER_TOTAL);
			}

			$oldCounter = (int)\CUserCounter::GetValue($this->arParams['USER_ID'], 'departments_counter', '**');
			if($oldCounter != (int)$this->arResult[ 'SECTION_MANAGE_COUNTER' ])
			{
				\CUserCounter::Set($this->arParams['USER_ID'], 'departments_counter', $this->arResult[ 'SECTION_MANAGE_COUNTER' ], '**', '', false);
			}
		}
	}

	// get states description
	$oListState = CTaskListState::getInstance($loggedInUserId);
	$arResult['VIEW_STATE'] = $oListState->getState();
	$arResult['VIEW_STATE_RAW'] = (new Signer())->sign($oListState->getRawState(), 'tasks.list.controls');

	$arResult["LIST_CTRL"] = $oListCtrl = CTaskListCtrl::getInstance($arParams['USER_ID']);
	$oListCtrl->useState($oListState);

	if ($arParams["GROUP_ID"] > 0)
		$oListCtrl->setFilterByGroupId( (int) $arParams["GROUP_ID"] );
	else
		$oListCtrl->setFilterByGroupId(null);

	$selectedRoleId = $arResult['VIEW_STATE']['ROLE_SELECTED']['ID'];
	$selectedRoleName = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];

	$arResult['F_CREATED_BY'] = $arResult['F_RESPONSIBLE_ID'] = null;

	if (isset($_GET['F_RESPONSIBLE_ID']))
		$arResult['F_RESPONSIBLE_ID'] = $_GET['F_RESPONSIBLE_ID'];

	if (isset($_GET['F_CREATED_BY']))
		$arResult['F_CREATED_BY'] = $_GET['F_CREATED_BY'];

	if ($arResult['F_CREATED_BY'] || $arResult['F_RESPONSIBLE_ID'])
	{
		$arResult['~USER_NAMES'] = array();

		$rsUsers = CUser::GetList(
			'id',
			'asc',
			array("ID" => implode('|', array_filter(array($arResult['F_CREATED_BY'], $arResult['F_RESPONSIBLE_ID'])))),
			array(
				'FIELDS' => array(
					'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'ID'
				)
			)
		);

		while ($arUser = $rsUsers->fetch())
		{
			$arResult['~USER_NAMES'][$arUser['ID']] = CUser::FormatName(
				$arParams['NAME_TEMPLATE'],
				array(
					'NAME'        => $arUser['NAME'],
					'LAST_NAME'   => $arUser['LAST_NAME'],
					'SECOND_NAME' => $arUser['SECOND_NAME'],
					'LOGIN'       => $arUser['LOGIN']
				),
				$bUseLogin = true,
				$bHtmlSpecialChars = false
			);
		}
	}

	// Links for mode switching
	// this code supposed to be in template.php :(

	$arResult['VIEW_HREFS'] = array(
		'ROLES'           => array(),
		'VIEWS'           => array(),
		'TASK_CATEGORIES' => array(),
		'SUBMODES'        => array()
	);

	foreach ($arResult['VIEW_STATE']['ROLES'] as $roleCodeName => $roleData)
		$arResult['VIEW_HREFS']['ROLES'][$roleCodeName] = '?F_CANCEL=Y&F_STATE=sR' . base_convert($roleData['ID'], 10, 32);

	foreach ($arResult['VIEW_STATE']['VIEWS'] as $viewCodeName => $viewData)
		$arResult['VIEW_HREFS']['VIEWS'][$viewCodeName] = '?F_CANCEL=Y&F_STATE=sV' . base_convert($viewData['ID'], 10, 32);

	$curUserFilterSwitch = '';
	if ($arResult['F_CREATED_BY'])
		$curUserFilterSwitch .= '&F_CREATED_BY=' . (int) $arResult['F_CREATED_BY'];
	if ($arResult['F_RESPONSIBLE_ID'])
		$curUserFilterSwitch .= '&F_RESPONSIBLE_ID=' . (int) $arResult['F_RESPONSIBLE_ID'];

	$inRoles = $arResult['VIEW_STATE']['SECTION_SELECTED']['ID'] == CTaskListState::VIEW_SECTION_ROLES;

	$curRoleSwitch = '&F_STATE[]=sR' . base_convert($arResult['VIEW_STATE']['ROLE_SELECTED']['ID'], 10, 32);
	foreach ($arResult['VIEW_STATE']['TASK_CATEGORIES'] as $categoryCodeName => $categoryData)
	{
		$categoryCode = base_convert($categoryData['ID'], 10, 32);

		if($inRoles)
		{
			// link in case of special preset
			$arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName] = '?F_CANCEL=Y' . $curRoleSwitch . $curUserFilterSwitch . '&F_STATE[]=sC' . $categoryCode;
		}
		else
		{
			// link in case of role
			$arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName] = '?F_CANCEL=Y&F_STATE[]=sC'.$categoryCode.'&F_FILTER_SWITCH_PRESET='.intval($arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['ID']);
		}
	}

	foreach ($arResult['VIEW_STATE']['SUBMODES'] as $submodeCodeName => $submodeData)
	{
		if ($submodeData['SELECTED'] === 'Y')
			$cmd = 'd';		// disable
		else
			$cmd = 'e';		// enable

		$arResult['VIEW_HREFS']['SUBMODES'][$submodeCodeName] = '?F_CANCEL=Y&F_STATE=' . $cmd . 'S' . base_convert($submodeData['ID'], 10, 32);
	}

	if(is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']))
	{
		$arResult['VIEW_HREFS']['SPECIAL_PRESETS'] = array();

		foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $preset)
		{
			$arResult['VIEW_HREFS']['SPECIAL_PRESETS'][$presetId] = '?'.(isset($_GET['VIEW']) ? 'VIEW='.intval($_GET['VIEW']).'&' : '').'F_CANCEL=Y&F_FILTER_SWITCH_PRESET='.$presetId.'&F_STATE[]=sC'.CTaskListState::encodeState(CTaskListState::VIEW_TASK_CATEGORY_ALL);
		}
	}

	if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
	{
		$counter = Counter::getInstance($arParams['USER_ID']);

		$arResult['VIEW_COUNTERS'] = array(
			'TOTAL' => array(
				'COUNTER' => $counter->get(Counter\CounterDictionary::COUNTER_MEMBER_TOTAL)
			),
			'ROLES' => array(
				'VIEW_ROLE_RESPONSIBLE' => array(
					'TOTAL' => array(
						'COUNTER' => $counter->get(Counter\CounterDictionary::COUNTER_MY),//$oListCtrl->getUserRoleCounter(CTaskListState::VIEW_ROLE_RESPONSIBLE),
						'COUNTER_ID' => Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_MY)
					)
				),
				'VIEW_ROLE_ACCOMPLICE' => array(
					'TOTAL' => array(
						'COUNTER' => $counter->get(Counter\CounterDictionary::COUNTER_ACCOMPLICES),
						'COUNTER_ID' => Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_ACCOMPLICES)
					)
				),
				'VIEW_ROLE_ORIGINATOR' => array(
					'TOTAL' => array(
						'COUNTER' => $counter->get(Counter\CounterDictionary::COUNTER_ORIGINATOR),
						'COUNTER_ID' => Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_ORIGINATOR)
					)
				),
				'VIEW_ROLE_AUDITOR' => array(
					'TOTAL' => array(
						'COUNTER' => $counter->get(Counter\CounterDictionary::COUNTER_AUDITOR),
						'COUNTER_ID' => Counter\CounterDictionary::getCounterId(Counter\CounterDictionary::COUNTER_AUDITOR)
					)
				)
			)
		);

		// set extended counter info
		switch($selectedRoleName)
		{
			case 'VIEW_ROLE_RESPONSIBLE':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
					)
				);

			case 'VIEW_ROLE_ACCOMPLICE':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_NEW'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_NEW
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
					)
				);

			case 'VIEW_ROLE_AUDITOR':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
					)
				);
			break;

			case 'VIEW_ROLE_ORIGINATOR':
				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_WAIT_CTRL'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL
					)
				);

				$arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleName]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
					'COUNTER' => $oListCtrl->getCounter(
						$selectedRoleId,
						CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
					)
				);
			break;
		}

		// Set plural forms
		$arResult['VIEW_COUNTERS']['TOTAL']['PLURAL'] = Loc::getPluralForm((int)$arResult['VIEW_COUNTERS']['TOTAL']['COUNTER']);
		foreach ($arResult['VIEW_COUNTERS']['ROLES'] as $roleId => $arData)
		{
			foreach ($arData as $counterId => $arCounter)
			{
				$arResult['VIEW_COUNTERS']['ROLES'][$roleId][$counterId]['PLURAL'] = Loc::getPluralForm((int)$arCounter['COUNTER']);
			}
		}
	}

	$arResult['VIEW_SECTION_ADVANCED_FILTER_HREF'] = '?F_CANCEL=Y&F_SECTION=ADVANCED';

	$arResult['MARK_SECTION_ALL'] = 'N';

	if (
		($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER')
		&& ($arParams['MARK_SECTION_PROJECTS'] === 'N')
		&& ($arParams['MARK_SECTION_MANAGE'] === 'N')
		&& ($arParams['MARK_SECTION_REPORTS'] === 'N')
		&& ($arParams['MARK_SECTION_EMPLOYEE_PLAN'] === 'N')
	)
	{
		$arResult['MARK_SECTION_ALL'] = 'Y';
	}

	$arResult['MARK_SPECIAL_PRESET'] = 'N';

	if($arResult['MARK_SECTION_ALL'] == 'Y' && is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']))
	{
		foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $preset)
		{
			if($preset['SELECTED'] == 'Y')
			{
				$arResult['MARK_SPECIAL_PRESET'] = 'Y';
				$arResult['MARK_SECTION_ALL'] = 'N'; // special preset cancels MARK_SECTION_ALL
				break;
			}
		}
	}
}

// arResult better formatting

$arResult['SELECTED_SECTION_NAME'] = $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'];

if (isset($arResult['VIEW_COUNTERS'], $arResult['VIEW_STATE']))
{
	if ($arResult['VIEW_STATE']['SECTION_SELECTED']['ID'] === CTaskListState::VIEW_SECTION_ROLES) // work only in "roles" state
	{
		$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];

		$arResult['SELECTED_ROLE_NAME']          = $selectedRoleCodename;
		$arResult['SELECTED_TASK_CATEGORY_NAME'] = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];

		$arResult['SELECTED_ROLE_COUNTER'] = array(
			'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['TOTAL']['COUNTER']
		);

		$arResult['SELECTED_ROLE_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['SELECTED_ROLE_COUNTER']['VALUE']);

		$arResult['TASKS_NEW_COUNTER'] = 				null;
		$arResult['TASKS_EXPIRED_COUNTER']= 			null;
		$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER'] = null;
		$arResult['TASKS_WAIT_CTRL_COUNTER'] = 			null;

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_NEW']['COUNTER']))
		{
			$arResult['TASKS_NEW_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_NEW']['COUNTER']
			);

			$arResult['TASKS_NEW_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['TASKS_NEW_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']))
		{
			$arResult['TASKS_EXPIRED_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED']['COUNTER']
			);

			$arResult['TASKS_EXPIRED_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['TASKS_EXPIRED_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']))
		{
			$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES']['COUNTER']
			);

			$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']))
		{
			$arResult['TASKS_WAIT_CTRL_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WAIT_CTRL']['COUNTER']
			);

			$arResult['TASKS_WAIT_CTRL_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']);
		}

		if (isset($arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']))
		{
			$arResult['TASKS_WO_DEADLINE_COUNTER'] = array(
				'VALUE'  => $arResult['VIEW_COUNTERS']['ROLES'][$selectedRoleCodename]['VIEW_TASK_CATEGORY_WO_DEADLINE']['COUNTER']
			);

			$arResult['TASKS_WO_DEADLINE_COUNTER']['PLURAL'] = Loc::getPluralForm((int)$arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']);
		}
	}
}

$arResult['SEARCH_STRING'] = null;
$arResult['ADV_FILTER'] = array('F_ADVANCED' => 'N');
if (
	isset($arParams['ADV_FILTER']['F_ADVANCED'])
	&& ($arParams['ADV_FILTER']['F_ADVANCED'] === 'Y')
)
{
	$arResult['ADV_FILTER'] = $arParams['ADV_FILTER'];
	if (isset($arParams['ADV_FILTER']['F_META::ID_OR_NAME']))
		$arResult['SEARCH_STRING'] = $arParams['ADV_FILTER']['F_META::ID_OR_NAME'];
	elseif (isset($arParams['ADV_FILTER']['F_TITLE']))
		$arResult['SEARCH_STRING'] = $arParams['ADV_FILTER']['F_TITLE'];
}

// from\for switch
$queryList = $request->getQueryList();
$arResult['FROM_FOR_SWITCH'] = isset($queryList['SW_FF']) && $queryList['SW_FF'] == 'FROM' ? 'FROM' : 'FOR';

//Project and User selectors
$arResult["DESTINATION"] = Bitrix\Tasks\Integration\SocialNetwork::getLogDestination('TASKS', array(
	'USE_PROJECTS' => 'Y'
));

$this->IncludeComponentTemplate();
