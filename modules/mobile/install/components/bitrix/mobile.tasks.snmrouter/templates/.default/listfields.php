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
$gridId = "mobile_tasks_list_".$columnsContextId;
$selectedFields = array("STATUS", "DEADLINE", "CREATED_BY", "PRIORITY", "FAVORITES");
if ($columnsContextId == CTaskListState::VIEW_ROLE_RESPONSIBLE ||
	$columnsContextId == CTaskColumnContext::CONTEXT_ACCOMPLICE)
{
	$selectedFields = array("STATUS", "DEADLINE", "RESPONSIBLE_ID", "PRIORITY", "FAVORITES");
}
$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $gridId);

$fields = array(
	'ID' => array('id' => 'ID', 'name' => "ID", 'class' => '', 'type' => ''),
	'STATUS' => array('id' => 'STATUS', 'name' => GetMessage('TASK_COLUMN_STATUS'), 'class' => '', 'type' => ''),
	'DEADLINE' => array('id' => 'DEADLINE', 'name' => GetMessage('TASK_COLUMN_DEADLINE'), 'class' => 'date', 'type' => 'date'),
	'CREATED_BY' => array('id' => 'CREATED_BY', 'name' => GetMessage('TASK_COLUMN_CREATED_BY'), 'class' => 'username'),
	'RESPONSIBLE_ID' => array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('TASK_COLUMN_RESPONSIBLE_ID'), 'class' => 'username', 'type' => ''),
	'PRIORITY' => array('id' => 'PRIORITY', 'name' => GetMessage('TASK_COLUMN_PRIORITY'), 'class' => '', 'type' => ''),
	'MARK' => array('id' => 'MARK', 'name' => GetMessage('TASK_COLUMN_MARK'), 'class' => '', 'type' => ''),
	'GROUP_ID' => array('id' => 'GROUP_ID', 'name' => GetMessage('TASK_COLUMN_GROUP_ID'), 'class' => '', 'type' => 'date'),
	'TIME_ESTIMATE' => array('id' => 'TIME_ESTIMATE', 'name' => GetMessage('TASK_COLUMN_TIME_ESTIMATE'), 'class' => '', 'type' => 'date'),
	'ALLOW_TIME_TRACKING' => array('id' => 'ALLOW_TIME_TRACKING', 'name' => GetMessage('TASK_COLUMN_ALLOW_TIME_TRACKING'), 'class' => 'date', 'type' => 'date'),
	'TIME_SPENT_IN_LOGS' => array('id' => 'TIME_SPENT_IN_LOGS', 'name' => GetMessage('TASK_COLUMN_TIME_SPENT_IN_LOGS'), 'class' => '', 'type' => 'date'),
	'ALLOW_CHANGE_DEADLINE' => array('id' => 'ALLOW_CHANGE_DEADLINE', 'name' => GetMessage('TASK_COLUMN_ALLOW_CHANGE_DEADLINE'), 'class' => '', 'type' => 'date'),
	'CREATED_DATE' => array('id' => 'CREATED_DATE', 'name' => GetMessage('TASK_COLUMN_CREATED_DATE'), 'class' => '', 'type' => 'date'),
	'CHANGED_DATE' => array('id' => 'CHANGED_DATE', 'name' => GetMessage('TASK_COLUMN_CHANGED_DATE'), 'class' => '', 'type' => 'date'),
	//'UF_CRM_TASK' => array('id' => 'CLOSED_DATE', 'name' => "CRM", 'class' => '', 'type' => 'date'),
	'CLOSED_DATE' => array('id' => 'CLOSED_DATE', 'name' => GetMessage('TASK_COLUMN_CLOSED_DATE'), 'class' => '', 'type' => 'date'),
	'FAVORITES' => array('id' => 'FAVORITES', 'name' => GetMessage('TASK_COLUMN_FAVORITES'), 'class' => '', 'type' => ''),
);

$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
	"GRID_ID" => $gridId,
	"ALL_FIELDS" => $fields,
	"SELECTED_FIELDS" => $selectedFields,
	"EVENT_NAME" => "onTasksListFields"
));
