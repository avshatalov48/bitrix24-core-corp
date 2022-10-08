<?php
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}
if ($siteId)
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/**
 * @global CUser $USER
 */

if(!CModule::IncludeModule('crm'))
	die();

global $DB, $APPLICATION;

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

CUtil::JSPostUnescape();

$action = !empty($_REQUEST['ajax_action']) ? $_REQUEST['ajax_action'] : null;

if (empty($action))
	die('Unknown action!');

$APPLICATION->ShowAjaxHead();
$action = mb_strtoupper($action);

$sendResponse = function($data, array $errors = array(), $plain = false)
{
	if ($data instanceof Bitrix\Main\Result)
	{
		$errors = $data->getErrorMessages();
		$data = $data->getData();
	}

	$result = array('DATA' => $data, 'ERRORS' => $errors);
	$result['SUCCESS'] = count($errors) === 0;
	if(!defined('PUBLIC_AJAX_MODE'))
	{
		define('PUBLIC_AJAX_MODE', true);
	}
	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

	if ($plain)
	{
		$result = $result['DATA'];
	}

	echo \Bitrix\Main\Web\Json::encode($result);
	CMain::FinalActions();
	die();
};
$sendError = function($error) use ($sendResponse)
{
	$sendResponse(array(), array($error));
};

switch ($action)
{
	case 'ACTIVITY_VIEW':
		$APPLICATION->IncludeComponent('bitrix:crm.activity.planner',
			'.default',
			array(
				'ACTION' => 'VIEW',
				'ELEMENT_ID' => isset($_REQUEST['activity_id'])? (int) $_REQUEST['activity_id'] : 0,
				'CALENDAR_EVENT_ID' => isset($_REQUEST['calendar_event_id'])? (int) $_REQUEST['calendar_event_id'] : 0
			)
		);
		break;
	case 'ACTIVITY_EDIT':
		$APPLICATION->IncludeComponent('bitrix:crm.activity.planner',
			'.default',
			array(
				'ACTION' => 'EDIT',
				'ELEMENT_ID' => isset($_REQUEST['ID'])? (int) $_REQUEST['ID'] : 0,
				'CALENDAR_EVENT_ID' => isset($_REQUEST['CALENDAR_EVENT_ID'])? (int) $_REQUEST['CALENDAR_EVENT_ID'] : 0,
				'TYPE_ID' => isset($_REQUEST['TYPE_ID']) ? (int) $_REQUEST['TYPE_ID'] : 0,
				'PROVIDER_ID' => isset($_REQUEST['PROVIDER_ID']) ? (string) $_REQUEST['PROVIDER_ID'] : 0,
				'PROVIDER_TYPE_ID' => isset($_REQUEST['PROVIDER_TYPE_ID']) ? (string) $_REQUEST['PROVIDER_TYPE_ID'] : 0,
				'OWNER_ID' => isset($_REQUEST['OWNER_ID']) ? (int) $_REQUEST['OWNER_ID'] : 0,
				'OWNER_TYPE_ID' => isset($_REQUEST['OWNER_TYPE_ID']) ? (int) $_REQUEST['OWNER_TYPE_ID'] : 0,
				'OWNER_TYPE' => isset($_REQUEST['OWNER_TYPE']) ? (string) $_REQUEST['OWNER_TYPE'] : 0,
				'PLANNER_ID' =>  isset($_REQUEST['PLANNER_ID']) ? (string) $_REQUEST['PLANNER_ID'] : 0,
				'FROM_ACTIVITY_ID' =>  isset($_REQUEST['FROM_ACTIVITY_ID']) ? (int) $_REQUEST['FROM_ACTIVITY_ID'] : 0,
				'ASSOCIATED_ENTITY_ID' => (int) $_REQUEST['ASSOCIATED_ENTITY_ID'],
			)
		);
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		break;
	case 'ACTIVITY_SAVE':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.planner');
		$result = CrmActivityPlannerComponent::saveActivity($_POST['data'], $curUser->getID(), SITE_ID);
		$sendResponse($result);
		break;
	case 'GET_DESTINATION_DATA':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.planner');
		$result = CrmActivityPlannerComponent::getDestinationData($_POST);
		$sendResponse($result);
		break;
	case 'SEARCH_DESTINATION_DEALS':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.planner');
		$result = CrmActivityPlannerComponent::searchDestinationDeals($_POST);
		$sendResponse($result, array(), true);
		break;
	case 'SEARCH_DESTINATION_ORDERS':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.planner');
		$result = CrmActivityPlannerComponent::searchDestinationOrders($_POST);
		$sendResponse($result, array(), true);
		break;
	case 'CREATE_CALL_LIST':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.call_list');
		$result = CrmActivityCallListComponent::createCallList($_POST);
		$sendResponse($result);
		break;
	case 'ADD_TO_CALL_LIST':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.call_list');
		$result = CrmActivityCallListComponent::addToCallList($_POST);
		$sendResponse($result);
		break;
	case 'PLANNER_UPDATE':
		if (CModule::IncludeModule("calendar"))
		{
			$result = [
				'entries' => [],
				'accessibility' => []
			];
			$userIds = [];

			if (isset($_REQUEST['entries']) && is_array($_REQUEST['entries']))
			{
				foreach($_REQUEST['entries'] as $user)
				{
					$userIds[] = $user;
					$arUser = CCalendar::GetUser($user);
					$result['entries'][] = [
						'type' => 'user',
						'id' => $user,
						'name' => CCalendar::GetUserName($arUser),
						'status' => '',
						'avatar' => CCalendar::GetUserAvatarSrc($arUser)
					];
				}
			}
			$from = CCalendar::Date(CCalendar::Timestamp($_REQUEST['from']), false);
			$to = CCalendar::Date(CCalendar::Timestamp($_REQUEST['to']), false);
			$currentEventId = 0;
			$activityId = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
			if ($activityId)
			{
				$activity = \CCrmActivity::getByID($activityId);
				if (is_array($activity) && is_set($activity['CALENDAR_EVENT_ID']))
				{
					$currentEventId = $activity['CALENDAR_EVENT_ID'];
				}
			}

			$result['dayOfWeekMonthFormat'] = \Bitrix\Main\Context::getCurrent()
				->getCulture()
				->getDayOfWeekMonthFormat();

			$accessibility = CCalendar::GetAccessibilityForUsers([
					'users' => $userIds,
					'from' => $from, // date or datetime in UTC
					'to' => $to, // date or datetime in UTC
					'curEventId' => $currentEventId,
					'getFromHR' => true
			]);

			foreach($accessibility as $userId => $entries)
			{
				$result['accessibility'][$userId] = [];

				foreach($entries as $entry)
				{
					if (isset($entry['DT_FROM']) && !isset($entry['DATE_FROM']))
					{
						$result['accessibility'][$userId][] = [
							'id' => $entry['ID'],
							'dateFrom' => $entry['DT_FROM'],
							'dateTo' => $entry['DT_TO'],
							'type' => $entry['FROM_HR'] ? 'hr' : 'event',
							'title' => $entry['NAME']
						];
					}
					else
					{
						$fromTs = CCalendar::Timestamp($entry['DATE_FROM']);
						$toTs = CCalendar::Timestamp($entry['DATE_TO']);
						if ($entry['DT_SKIP_TIME'] !== "Y")
						{
							$fromTs -= $entry['~USER_OFFSET_FROM'];
							$toTs -= $entry['~USER_OFFSET_TO'];
						}
						$result['accessibility'][$userId][] = [
							'id' => $entry['ID'],
							'dateFrom' => CCalendar::Date($fromTs, $entry['DT_SKIP_TIME'] != 'Y'),
							'dateTo' => CCalendar::Date($toTs, $entry['DT_SKIP_TIME'] != 'Y'),
							'type' => $entry['FROM_HR'] ? 'hr' : 'event',
							'title' => $entry['NAME']
						];
					}
				}
			}
			$sendResponse(["DATA" => $result, 'ERRORS' => []], [], true);
		}
		break;

	default:
		die('Unknown action!');
		break;
}