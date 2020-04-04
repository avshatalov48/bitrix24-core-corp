<?php

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('PUBLIC_AJAX_MODE', true);

$SITE_ID = '';
if (isset($_GET["SITE_ID"]) && is_string($_GET['SITE_ID']))
	$SITE_ID = substr(preg_replace("/[^a-z0-9_]/i", "", $_GET["SITE_ID"]), 0, 2);

if ($SITE_ID != '')
	define("SITE_ID", $SITE_ID);

// KIND OF QUERY PROCESSOR HERE. IT IS USED BY SEVERAL OTHER COMPONENTS, SO BETTER TO CREATE A SEPARATE CLASS BASED ON IT, AND THEN MARK THIS COMPONENT AS DEPRECATED

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CUtil::JSPostUnescape();
CModule::IncludeModule('tasks');
CModule::IncludeModule('socialnetwork');

Loc::loadMessages(__FILE__);

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;
$nameTemplate = null;
$batch = null;

if (isset($_POST['nameTemplate']))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_POST['nameTemplate']), $matches = null);
	$nameTemplate = implode("", $matches[0]);
}
else
	$nameTemplate = CSite::GetNameFormat(false);

$batchId = 'unknown';

if (isset($_POST['batch']))
	$batch = $_POST['batch'];

if (isset($_POST['batchId']))
	$batchId = $_POST['batchId'];

if ( ! is_array($batch) )
{
	CTaskAssert::log(
		'Batch not given. File: ' . __FILE__, 
		CTaskAssert::ELL_ERROR
	);
	exit();
}

if ( ! check_bitrix_sessid() )
	exit();


function BXTasksResolveDynaParamValue($request, $arData)
{
	// Is task id the result of previous operation in batch?
	if (BXTasksIsDynaParamValue($request))
		$request = BXTasksParseAndGetDynaParamValue($arData, $request);

	return ($request);
}


/**
 * determine if request starts from "#RC#"
 */
function BXTasksIsDynaParamValue($request)
{
	if ( ! is_string($request) )
		return (false);

	return(substr($request, 0, 4) === '#RC#');
}


/**
 * 
 * @param array $arData with element "arDataName"
 * @param string $strRequest, for example: #RC#arDataName#-2#field1#field2#...#fieldN
 */
function BXTasksParseAndGetDynaParamValue($arData, $strRequest)
{
	CTaskAssert::assert(
		is_array($arData)
		&& is_string($strRequest) 
		&& (substr($strRequest, 0, 4) === '#RC#')
	);

	$dataCount = count($arData);

	$strToParse   = substr($strRequest, 4);
	$arrayToParse = explode('#', $strToParse);

	CTaskAssert::assert(
		is_array($arrayToParse)
		&& (count($arrayToParse) >= 3)
		&& isset($arData[$arrayToParse[0]])		// in 0th element - arDataName
		&& CTaskAssert::isLaxIntegers($arrayToParse[1])	// there is relative index
		&& ($arrayToParse[1] < 0)	// relative index must be < 0
	);

	$arRequestedData = $arData[$arrayToParse[0]];

	$curDataIndex   = count($arRequestedData) - 1;
	$deltaIndex     = (int) $arrayToParse[1];
	$requestedIndex = $curDataIndex + $deltaIndex + 1;	// +1 because last data item mustn't be in data array yet

	if ( ! isset($arRequestedData[$requestedIndex]) )
		return (null);

	// Now, iterate throws given fields
	$maxIndex = count($arrayToParse) - 1;

	$arIteratedData = $arRequestedData[$requestedIndex];

	for ($i = 2; $i <= $maxIndex; $i++)
	{
		$requestedNthFieldName = $arrayToParse[$i];
		if ( ! isset($arIteratedData[$requestedNthFieldName]) )
			return (null);

		$arIteratedData = $arIteratedData[$requestedNthFieldName];
	}

	return ($arIteratedData);
}


$status = 'unknown';
$breakExecution = false;
try
{
	$loggedInUserId = $GLOBALS['USER']->GetID();
	CTaskAssert::assert($loggedInUserId >= 1);
	$loggedInUserId = (int) $loggedInUserId;

	$operationIndex = 0;
	$arOperationsResults = array();
	foreach ($batch as $arAction)
	{
		$APPLICATION->RestartBuffer();
		CTaskAssert::assert(isset($arAction['operation']));

		$arCurOperationResult = array();
		switch ($arAction['operation'])
		{
			case 'CTaskItem::add()':
				// convert multiple UF_ fields to arrays, if they not are
				$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('TASKS_TASK');

				CTaskAssert::assert(isset($arAction['taskData']) && is_array($arAction['taskData']));

				// Don't allow fields started not from the letter, because they will not be filtered during DB query
				$testForLetters = '';
				foreach (array_keys($arAction['taskData']) as $fieldName)
					$testForLetters .= substr($fieldName, 0, 1);

				CTaskAssert::assert((bool)preg_match('/^[A-Za-z]*$/', $testForLetters));

				foreach($arUserFields as $arUserField)
				{
					if ($arUserField['EDIT_IN_LIST'] !== 'Y')
						continue;

					if ( ! array_key_exists($arUserField['FIELD_NAME'], $arAction['taskData']) )
						continue;

					$value = $arAction['taskData'][$arUserField['FIELD_NAME']];

					if ( ($arUserField['MULTIPLE'] === 'Y') && ( ! is_array($value) ) )
						$arAction['taskData'][$arUserField['FIELD_NAME']] = array($value);
				}

				$arErrors = array();
				$justCreatedTaskId = false;
				try
				{		
					$oTask = CTaskItem::add($arAction['taskData'], $loggedInUserId);
					$justCreatedTaskId = $oTask->getId();
				}
				catch (Exception $e)
				{
					if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
						$arErrors = unserialize($e->GetMessage());
					else
					{
						$arErrors[] = array(
							'text' => 'UNKNOWN ERROR OCCURED',
							'id'   => 'ERROR_TASKS_UNKNOWN'
						);
					}
				}

				$arCurOperationResult = array(
					'returnValue'       => null,	// because CTaskItem::add() returns an PHP object
					'justCreatedTaskId' => $justCreatedTaskId,
					'errors'            => $arErrors
				);

				if ($justCreatedTaskId === false)
					$breakExecution = true;
			break;


			case 'CTaskItem::getData()':
			case 'CTaskItem::getTaskData()':
			case 'CTaskItem::getAllowedTaskActions()':
			case 'CTaskItem::getAllowedTaskActionsAsStrings()':
			case 'CTaskItem::update()':
			case 'CTaskItem::stopWatch()':
			case 'CTaskItem::startWatch()':
			case 'CTaskItem::complete()':
			case 'CTaskItem::startExecution()':
			case 'CTaskItem::pauseExecution()':
			case 'CTaskItem::renew()':
			case 'CTaskItem::defer()':
			case 'CTaskItem::disapprove()':
			case 'CTaskItem::approve()':
			case 'CTaskItem::startExecutionOrRenewAndStart':
				CTaskAssert::assert(
					isset($arAction['taskData'], $arAction['taskData']['ID'])
				);

				// Don't allow fields started not from the letter, because they will not be filtered during DB query
				$testForLetters = '';
				foreach (array_keys($arAction['taskData']) as $fieldName)
					$testForLetters .= substr($fieldName, 0, 1);

				CTaskAssert::assert((bool)preg_match('/^[A-Za-z]*$/', $testForLetters));

				// Resolve task id if it is the result of previous operation in batch
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['taskData']['ID'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId);

				$oTask = CTaskItem::getInstanceFromPool($taskId, $loggedInUserId);

				$returnValue = null;
				switch ($arAction['operation'])
				{
					case 'CTaskItem::getData()':
					case 'CTaskItem::getTaskData()':
						$arTaskData = $oTask->getData($bSpecialChars = false);
						$arTaskData['TAGS'] = $oTask->getTags();
						$arTaskData['FILES'] = $oTask->getFiles();
						$arTaskData['DEPENDS_ON'] = $oTask->getDependsOn();
						$returnValue = $arTaskData;
					break;

					case 'CTaskItem::getAllowedTaskActions()':
						$returnValue = $oTask->getAllowedTaskActions();
					break;

					case 'CTaskItem::getAllowedTaskActionsAsStrings()':
						$returnValue = $oTask->getAllowedTaskActionsAsStrings();
					break;

					case 'CTaskItem::update()':
						$returnValue = $oTask->update($arAction['taskData']);
					break;

					case 'CTaskItem::stopWatch()':
						$returnValue = $oTask->stopWatch();
					break;

					case 'CTaskItem::startWatch()':
						$returnValue = $oTask->startWatch();
					break;

					case 'CTaskItem::complete()':
						$returnValue = $oTask->complete();
					break;

					case 'CTaskItem::startExecution()':
						$returnValue = $oTask->startExecution();
					break;

					case 'CTaskItem::pauseExecution()':
						$returnValue = $oTask->pauseExecution();
					break;

					case 'CTaskItem::renew()':
						$returnValue = $oTask->renew();
					break;

					case 'CTaskItem::approve()':
						$returnValue = $oTask->approve();
					break;

					case 'CTaskItem::disapprove()':
						$returnValue = $oTask->disapprove();
					break;

					case 'CTaskItem::defer()':
						$returnValue = $oTask->defer();
					break;

					case 'CTaskItem::startExecutionOrRenewAndStart':
						if (
							( ! $oTask->isActionAllowed(CTaskItem::ACTION_START) )
							&& $oTask->isActionAllowed(CTaskItem::ACTION_RENEW)
						)
						{
							$returnValue = $oTask->renew();
						}

						if ($oTask->isActionAllowed(CTaskItem::ACTION_START))
							$returnValue = $oTask->startExecution();
					break;

					default:
						throw new Exception('Unknown operation: ' . $arAction['operation']);
					break;
				}

				$arCurOperationResult = array(
					'returnValue'     => $returnValue,
					'requestedTaskId' => $taskId
				);
			break;


			case 'XCHG EAX, EAX':
			case 'NOP':
			case 'NOOP':
				$arCurOperationResult = array('returnValue' => null);
			break;


			case 'CUser::FormatName()':
				CTaskAssert::assert(
					isset($arAction['userData'], $arAction['userData']['ID'])
				);

				// Resolve user id if it is the result of previous operation in batch
				$userId = BXTasksResolveDynaParamValue(
					$arAction['userData']['ID'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($userId);

				$nt = $nameTemplate;
				if (isset($arAction['params'], $arAction['params']['nameTemplate']))
				{
					preg_match_all(
						"/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/",
						$arAction['params']['nameTemplate'],
						$matches
					);

					$nt = implode('', $matches[0]);
				}

				$rsUser = CUser::GetList(
					$by = 'ID', $order = 'ASC', 
					array('ID' => $userId), 
					array('FIELDS' => array('NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'))
				);

				$returnValue = null;

				if ($arUser = $rsUser->Fetch())
				{
					$returnValue = CUser::FormatName(
						$nt, 
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

				$arCurOperationResult = array(
					'returnValue'     => $returnValue,
					'requestedUserId' => $userId
				);
			break;


			case 'tasksRenderJSON() && tasksRenderListItem()':
				CTaskAssert::assert(
					isset($arAction['taskData'], $arAction['taskData']['ID'])
				);

				$columnsIds = null;
				if (isset($arAction['columnsIds']))
					$columnsIds = array_map('intval', $arAction['columnsIds']);

				$arFilter = array();
				if (isset($arAction['arFilter']) && is_array($arAction['arFilter']))
					$arFilter = $arAction['arFilter'];

				// Is task id the result of previous operation in batch?
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['taskData']['ID'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId);

				$oTask = CTaskItem::getInstanceFromPool($taskId, $loggedInUserId);

				$returnValue = null;

				$arTask = $oTask->getData($bSpecialChars = false);

				if ($arTask['GROUP_ID'])
				{
					$arTask['GROUP_NAME'] = '';
					$arGroup = CSocNetGroup::GetByID($arTask['GROUP_ID']);

					if ($arGroup)
						$arTask['GROUP_NAME'] = $arGroup['~NAME'];
				}

				$childrenCount = 0;
				$rsChildrenCount = CTasks::GetChildrenCount($arFilter = array(), array($taskId));
				if ($rsChildrenCount)
				{
					if ($arChildrens = $rsChildrenCount->Fetch())
						$childrenCount = $arChildrens['CNT'];
				}

				$arPathes = array(
					'PATH_TO_TASKS_TASK' => str_replace(
						"#user_id#",
						$loggedInUserId,
						COption::GetOptionString('tasks', 'paths_task_user_action', null, $SITE_ID)
					)
				);

				$arTaskEscaped = array();
				foreach ($arTask as $key => $value)
				{
					if (is_array($value))
					{
						foreach ($value as $key2 => $value2)
							$arTaskEscaped[$key][$key2] = htmlspecialcharsbx($value2);
					}
					else
						$arTaskEscaped[$key] = htmlspecialcharsbx($value);
				}

				ob_start();
				{
					$params = array(
						"PATHS"         => $arPathes,
						"PLAIN"         => false,
						"DEFER"         => true,
						"SITE_ID"       => $SITE_ID,
						"TASK_ADDED"    => true,
						'IFRAME'        => 'N',
						'NAME_TEMPLATE' => $nameTemplate,
						'DATA_COLLECTION' => array(
							array(
								"CHILDREN_COUNT"   => $childrenCount,
								"DEPTH"            => 0,
								"UPDATES_COUNT"    => 0,
								"PROJECT_EXPANDED" => true,
								'ALLOWED_ACTIONS'  => null,
								"TASK"             => $arTaskEscaped
							)
						)
					);

					if ($columnsIds !== null)
						$params['COLUMNS_IDS'] = $columnsIds;

					$APPLICATION->IncludeComponent(
						'bitrix:tasks.list.items', '.default',
						$params, null, array("HIDE_ICONS" => "Y")
					);
				}
				$html = ob_get_clean();

				ob_start();
				$arAdditionalFields = array();
				tasksRenderJSON(
					$arTask,
					$childrenCount,
					$arPathes,
					$bParent = true,
					$bGant = false,
					false,
					$nameTemplate,
					$arAdditionalFields
				);
				$json = ob_get_clean();

				$returnValue = array(
					'tasksRenderListItem' => $html,
					'tasksRenderJSON'     => $json
				);

				$arCurOperationResult = array(
					'returnValue'     => $returnValue,
					'requestedTaskId' => $taskId
				);
			break;


			case 'CSocNetGroup::GetByID()':
				CTaskAssert::assert(
					isset($arAction['groupData'], $arAction['groupData']['ID'])
				);
				$groupId = $arAction['groupData']['ID'];
				CTaskAssert::assertLaxIntegers($groupId);

				$arGroupData = array(
					'ID'             => (int) $groupId,
					'~ID'            => (int) $groupId,
					'NAME'           => '',
					'~NAME'          => '',
					'SUBJECT_NAME'   => '',
					'~SUBJECT_NAME'  => '',
					'NAME_FORMATTED' => ''
				);

				$arGroup = CSocNetGroup::GetByID($groupId, $bCheckPermissions = false);

				if (
					is_array($arGroup)
					&& ( ! empty($arGroup) )
				)
				{
					$arGroupData = $arGroup;
				}

				$arCurOperationResult = array(
					'returnValue'      => $arGroupData,
					'requestedGroupId' => $groupId
				);
			break;


			case 'CTaskItem::addElapsedTime()':
				CTaskAssert::assert(
					isset(
						$arAction['elapsedTimeData'],
						$arAction['elapsedTimeData']['TASK_ID'],
						$arAction['elapsedTimeData']['MINUTES'],
						$arAction['elapsedTimeData']['COMMENT_TEXT']
					)
					&& (count($arAction['elapsedTimeData']) === 3)
					&& is_string($arAction['elapsedTimeData']['COMMENT_TEXT'])
				);

				// Is task id the result of previous operation in batch?
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['elapsedTimeData']['TASK_ID'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId, $arAction['elapsedTimeData']['MINUTES']);

				$justCreatedLogId = false;
				$arErrors = array();
				try
				{
					$oTask = CTaskItem::getInstanceFromPool($taskId, $loggedInUserId);

					$arFields = array(
						'MINUTES'      => $arAction['elapsedTimeData']['MINUTES'],
						'COMMENT_TEXT' => $arAction['elapsedTimeData']['COMMENT_TEXT']
					);

					$justCreatedLogId = $oTask->addElapsedTime($arFields);
				}
				catch (Exception $e)
				{
					if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
						$arErrors = unserialize($e->GetMessage());
					else
					{
						$arErrors[] = array(
							'text' => 'UNKNOWN ERROR OCCURED',
							'id'   => 'ERROR_TASKS_UNKNOWN'
						);
					}
				}

				$arCurOperationResult = array(
					'returnValue'      => $justCreatedLogId,
					'justCreatedLogId' => $justCreatedLogId,
					'requestedData'    => $arAction['elapsedTimeData'],
					'errors'           => $arErrors
				);

				if ($justCreatedLogId === false)
					$breakExecution = true;
			break;


			case 'CTaskCheckListItem::add()':
				CTaskAssert::assert(
					isset(
						$arAction['taskId'],
						$arAction['checklistData'],
						$arAction['checklistData']['TITLE']
					)
					&& (count($arAction['checklistData']) === 1)
					&& is_string($arAction['checklistData']['TITLE'])
				);

				// Is task id the result of previous operation in batch?
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['taskId'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId);

				$justCreatedId = false;
				$arErrors = array();
				try
				{
					$oTask = CTaskItem::getInstanceFromPool($taskId, $loggedInUserId);

					$arFields = array(
						'TITLE' => $arAction['checklistData']['TITLE']
					);

					$oCheckListItem = CTaskCheckListItem::add($oTask, $arFields);
					$justCreatedId  = $oCheckListItem->getId();
				}
				catch (Exception $e)
				{
					if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
						$arErrors = unserialize($e->GetMessage());
					else
					{
						$arErrors[] = array(
							'text' => 'UNKNOWN ERROR OCCURED',
							'id'   => 'ERROR_TASKS_UNKNOWN'
						);
					}
				}

				$arCurOperationResult = array(
					'returnValue'   => $justCreatedId,
					'justCreatedId' => $justCreatedId,
					'requestedData' => $arAction['checklistData'],
					'errors'        => $arErrors
				);

				if ($justCreatedId === false)
					$breakExecution = true;
			break;


			case 'CTaskCheckListItem::complete()':
			case 'CTaskCheckListItem::renew()':
			case 'CTaskCheckListItem::delete()':
			case 'CTaskCheckListItem::isComplete()':
			case 'CTaskCheckListItem::update()':
			case 'CTaskCheckListItem::moveAfterItem()':
				CTaskAssert::assert(
					isset(
						$arAction['taskId'],
						$arAction['itemId']
					)
				);

				// Is task id the result of previous operation in batch?
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['taskId'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				// Is item id the result of previous operation in batch?
				$itemId = BXTasksResolveDynaParamValue(
					$arAction['itemId'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId, $itemId);

				$oTask = CTaskItem::getInstanceFromPool($taskId, $loggedInUserId);

				$oCheckListItem = new CTaskCheckListItem($oTask, $itemId);

				$returnValue = null;
				switch ($arAction['operation'])
				{
					case 'CTaskCheckListItem::moveAfterItem()':
						$insertAfterItemId = BXTasksResolveDynaParamValue(
							$arAction['insertAfterItemId'],
							array('$arOperationsResults' => $arOperationsResults)
						);

						CTaskAssert::assertLaxIntegers($insertAfterItemId);

						$oCheckListItem->moveAfterItem($insertAfterItemId);
					break;

					case 'CTaskCheckListItem::complete()':
						$oCheckListItem->complete();
					break;

					case 'CTaskCheckListItem::renew()':
						$oCheckListItem->renew();
					break;

					case 'CTaskCheckListItem::delete()':
						$oCheckListItem->delete();
					break;

					case 'CTaskCheckListItem::isComplete()':
						$returnValue = $oCheckListItem->isComplete();
					break;

					case 'CTaskCheckListItem::update()':
						$arFields = array();
						if (isset($arAction['checklistData']['TITLE']))
							$arFields['TITLE'] = $arAction['checklistData']['TITLE'];
						if (isset($arAction['checklistData']['IS_COMPLETE']))
							$arFields['IS_COMPLETE'] = $arAction['checklistData']['IS_COMPLETE'];
						try
						{
							$returnValue = $oCheckListItem->update($arFields);
						}
						catch (\TasksException $e)
						{
							$returnValue = false;
						}
					break;

					default:
						throw new Exception('Unknown operation: ' . $arAction['operation']);
					break;
				}

				$arCurOperationResult = array(
					'returnValue'     => $returnValue,
					'requestedItemId' => $itemId
				);
			break;


			case 'CTaskTimerManager::start()':
			case 'CTaskTimerManager::stop()':
				CTaskAssert::assert(
					isset($arAction['taskData'], $arAction['taskData']['ID'])
				);

				// Is task id the result of previous operation in batch?
				$taskId = BXTasksResolveDynaParamValue(
					$arAction['taskData']['ID'],
					array('$arOperationsResults' => $arOperationsResults)
				);

				CTaskAssert::assertLaxIntegers($taskId);

				$oTaskTimer = CTaskTimerManager::getInstance($loggedInUserId);

				if ($arAction['operation'] === 'CTaskTimerManager::start()')
					$oTaskTimer->start($taskId);
				elseif ($arAction['operation'] === 'CTaskTimerManager::stop()')
					$oTaskTimer->stop($taskId);
				else
					CTaskAssert::assert(false);

				$arCurOperationResult = array(
					'returnValue'     => null,
					'requestedTaskId' => $taskId
				);
			break;


			case 'CTaskTimerManager::getLastTimer()':
				$oTaskTimer = CTaskTimerManager::getInstance($loggedInUserId);

				$arCurOperationResult = array(
					'returnValue' => $oTaskTimer->getLastTimer()
				);
			break;


			case 'tasks.list::getOriginators()':
			case 'tasks.list::getResponsibles()':
				CTaskAssert::assert(
					isset($arAction['userId'], $arAction['groupId'], $arAction['rawState'])
				);

				CTaskAssert::assertLaxIntegers($arAction['userId'], $arAction['groupId']);
				CTaskAssert::assert(unserialize($arAction['rawState']) !== false);

				$oListState = CTaskListState::getInstance($loggedInUserId);
				$oListState->setRawState($arAction['rawState']); // just update current value of an option

				$oListCtrl = CTaskListCtrl::getInstance($arAction['userId']);
				$oListCtrl->useState($oListState); // just saving reference to $oListState inside $oListCtrl

				if ($arAction['groupId'] > 0)
				{
					$bGroupMode = true;
					$oListCtrl->setFilterByGroupId( (int) $arAction['groupId'] );
				}
				else
				{
					$bGroupMode = false;
					$oListCtrl->setFilterByGroupId(null);
				}

				$oFilter = CTaskFilterCtrl::GetInstance($arAction['userId'], $bGroupMode);
				$oListCtrl->useAdvancedFilterObject($oFilter); // just saving reference to $oFilter inside $oListCtrl

				$arFilter = array_merge($oListCtrl->getFilter(), $oListCtrl->getCommonFilter());

				if ($arAction['operation'] === 'tasks.list::getOriginators()')
				{
					$res = CTasks::GetOriginatorsByFilter($arFilter, $loggedInUserId);
				}
				else if ($arAction['operation'] === 'tasks.list::getResponsibles()')
				{
					$res = CTasks::GetResponsiblesByFilter($arFilter, $loggedInUserId);
				}
				else
				{
					throw new Exception('unknown operation: ' . $arAction['operation']);
				}

				$arUsers = array();
				while ($ar = $res->fetch())
				{
					$arUsers[$ar['USER_ID']] = array(
						'USER_ID'   => (int) $ar['USER_ID'],
						'TASKS_CNT' => (int) $ar['TASKS_CNT']
					);
				}

				if ( ! empty($arUsers) )
				{
					$rsUser = CUser::GetList(
						$by = 'ID', $order = 'ASC', 
						array('ID' => implode("|", array_keys($arUsers))),
						array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'))
					);

					while ($arUser = $rsUser->fetch())
					{
						$arUsers[$arUser['ID']]['NAME_FORMATTED'] = CUser::FormatName(
							$nameTemplate, 
							array(
								'NAME'        => $arUser['NAME'],
								'LAST_NAME'   => $arUser['LAST_NAME'],
								'SECOND_NAME' => $arUser['SECOND_NAME'],
								'LOGIN'       => $arUser['LOGIN']
							),
							$bUseLogin = true,
							$bHtmlSpecialChars = false
						);

						$arUsers[$arUser['ID']]['NAME']        = $arUser['NAME'];
						$arUsers[$arUser['ID']]['SECOND_NAME'] = $arUser['SECOND_NAME'];
						$arUsers[$arUser['ID']]['LAST_NAME']   = $arUser['LAST_NAME'];
						$arUsers[$arUser['ID']]['LOGIN']       = $arUser['LOGIN'];
					}
				}

				if ((LANGUAGE_ID === 'ru') || (LANGUAGE_ID === 'ua'))
				{
					usort(
						$arUsers,
						create_function(
							'$a,$b',
							'return strnatcasecmp($a["LAST_NAME"], $b["LAST_NAME"]);'
						)
					);
				}
				else
				{
					usort(
						$arUsers,
						create_function(
							'$a,$b',
							'return strnatcasecmp($a["NAME_FORMATTED"], $b["NAME_FORMATTED"]);'
						)
					);
				}

				$arCurOperationResult = array(
					'returnValue' => $arUsers
				);
			break;


			default:
				throw new Exception(
					'Unknown operation requested. File: ' . __FILE__ 
						. '; action: ' . $arAction['operation']
				);
			break;
		}

		$arCurOperationResult['requestedOperationName'] = $arAction['operation'];
		$arOperationsResults[$operationIndex] = $arCurOperationResult;
		$operationIndex++;

		if ($breakExecution)
			break;
	}

	if ( ! $breakExecution )
		$status = 'success';
	else
		$status = 'error occured';
}
catch (Exception $e)
{
	CTaskAssert::log(
		'Exception. Current file: ' . __FILE__ 
			. '; exception file: ' . $e->GetFile()
			. '; line: ' . $e->GetLine()
			. '; message: ' . $e->GetMessage(), 
		CTaskAssert::ELL_ERROR
	);

	$status = 'error occured';
}

$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
echo CUtil::PhpToJsObject(
	array(
		'status'        => $status,
		'repliesCount'  => count($arOperationsResults),
		'data'          => $arOperationsResults,
		'batchId'       => $batchId
	)
);
CMain::FinalActions(); // to make events work on bitrix24