<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 * @var CUser $USER
 */
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

CModule::IncludeModule("task");
$oListState = CTaskListState::getInstance($USER->getId());
$loggedInUserId = (int) $USER->GetID();
$arSwitchStateTo = array();
if (
	isset($_GET['F_STATE'])
	&& (
		is_array($_GET['F_STATE'])
		|| (strlen($_GET['F_STATE']) > 2)
	)
)
{
	$arSwitchStateTo = (array) $_GET['F_STATE'];
}
elseif ( ! (isset($_GET["F_CANCEL"]) || isset($_GET['F_FILTER_SWITCH_PRESET'])) )
{
	$currentRole = $oListState->getUserRole();
	$arSwitchStateTo = array(intval($currentRole) ? 'sR'.base_convert($currentRole, 10, 32) : 'sR400');
}

foreach ($arSwitchStateTo as $switchStateTo)
{
	if ($switchStateTo)
	{
		try
		{
			$symbol = substr($switchStateTo, 0, 2);
			$value = CTaskListState::decodeState(substr($switchStateTo, 2));

			switch ($symbol)
			{
				case 'sR':	// set role
					$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
					$oListState->setUserRole($value);
					$oListState->setTaskCategory(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS);
				break;

				case 'sV':	// set view
					$oListState->setViewMode($value);
				break;

				case 'sC':	// set category
					$oListState->setTaskCategory($value);
				break;

				case 'eS':	// enable submode
					$oListState->switchOnSubmode($value);
				break;

				case 'dS':	// disable submode
					$oListState->switchOffSubmode($value);
				break;
			}

			$oListState->saveState();
		}
		catch (TasksException $e)
		{
			CTaskAssert::logError(
				'[0x523d4e28] : $switchStateTo = ' . $switchStateTo . ' (' . $value . ');'
				. ' cur user role: ' . $oListState->getUserRole()
				. ' serialize($arSwitchStateTo) = ' . serialize($arSwitchStateTo)
			);
			// wrong user input, nothing to do here
		}
	}
}

$arResult['VIEW_STATE'] = $oListState->getState();

switch ($arResult['VIEW_STATE']['SECTION_SELECTED']['ID'])
{
	case CTaskListState::VIEW_SECTION_ROLES:
		switch ($arResult['VIEW_STATE']['ROLE_SELECTED']['ID'])
		{
			case CTaskListState::VIEW_ROLE_RESPONSIBLE:
				$columnsContextId = CTaskColumnContext::CONTEXT_RESPONSIBLE;
			break;

			case CTaskListState::VIEW_ROLE_ORIGINATOR:
				$columnsContextId = CTaskColumnContext::CONTEXT_ORIGINATOR;
			break;

			case CTaskListState::VIEW_ROLE_ACCOMPLICE:
				$columnsContextId = CTaskColumnContext::CONTEXT_ACCOMPLICE;
			break;

			case CTaskListState::VIEW_ROLE_AUDITOR:
				$columnsContextId = CTaskColumnContext::CONTEXT_AUDITOR;
			break;

			default:
				$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
			break;
		}
	break;

	case CTaskListState::VIEW_SECTION_ADVANCED_FILTER:
	default:
		$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
	break;
}

// order
if (($sortInOptions = CUserOptions::GetOption(
	'tasks:list:sort',
	'sort' . '_' . $columnsContextId,
	'none',
	$loggedInUserId
)) === "none")
{
	$sortInOptions = array("SORTING" => "ASC");
}
else
{
	$sortInOptions = unserialize($sortInOptions);
}

$gridId = "mobile_tasks_list_".$columnsContextId;

if (is_array($sortInOptions))
{
	$curOption = CUserOptions::GetOption("mobile.interface.grid", $gridId);
	$curOption = (is_array($curOption) ? $curOption : array());
	$curOption["sort_order"] = reset($sortInOptions);

	if ($curOption["sort_by"] != key($sortInOptions))
	{
		$curOption["sort_by"] = key($sortInOptions);
		CUserOptions::SetOption("mobile.interface.grid", $gridId, $curOption);
	}
}

$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
	"GRID_ID" => "mobile_tasks_list_".$columnsContextId,
	"SORT_FIELDS" => array(
		'ID' => array('id' => 'ID', 'name' => "ID", 'sort' => 'ID'),
		'TITLE' => array('id' => 'TITLE', 'name' => GetMessage('TASK_COLUMN_TITLE'), 'sort' => 'TITLE'),
		'DEADLINE' => array('id' => 'DEADLINE', 'name' => GetMessage('TASK_COLUMN_DEADLINE'), 'sort' => 'DEADLINE'),
		'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('TASK_COLUMN_CREATED_BY'), 'sort' => 'CREATED_BY'),
		'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('TASK_COLUMN_RESPONSIBLE_ID'), 'sort' => 'RESPONSIBLE_ID'),
		'PRIORITY' => array('id' => 'PRIORITY', 'name' => GetMessage('TASK_COLUMN_PRIORITY'), 'sort' => 'PRIORITY'),
		'MARK' => array('id' => 'MARK', 'name' => GetMessage('TASK_COLUMN_MARK'), 'sort' => 'MARK'),
		'TIME_ESTIMATE' => array('id' => 'TIME_ESTIMATE', 'name' => GetMessage('TASK_COLUMN_TIME_ESTIMATE'), 'sort' => 'TIME_ESTIMATE'),
		'ALLOW_TIME_TRACKING' => array('id' => 'ALLOW_TIME_TRACKING', 'name' => GetMessage('TASK_COLUMN_ALLOW_TIME_TRACKING'), 'sort' => 'ALLOW_TIME_TRACKING'),
		'CREATED_DATE' => array('id' => 'CREATED_DATE', 'name' => GetMessage('TASK_COLUMN_CREATED_DATE'), 'sort' => 'CREATED_DATE'),
		'CHANGED_DATE' => array('id' => 'CHANGED_DATE', 'name' => GetMessage('TASK_COLUMN_CHANGED_DATE'), 'sort' => 'CHANGED_DATE'),
		'CLOSED_DATE' => array('id' => 'CLOSED_DATE', 'name' => GetMessage('TASK_COLUMN_CLOSED_DATE'), 'sort' => 'CLOSED_DATE'),
		'SORTING' => array('id' => 'SORTING', 'name' => GetMessage('TASK_COLUMN_SORTING'), 'sort' => 'SORTING')
	),
	"EVENT_NAME" => "onTasksListSort"
));


