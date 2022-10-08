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
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid())
{
	die();
}

CUtil::JSPostUnescape();

$action = !empty($_REQUEST['ajax_action']) ? $_REQUEST['ajax_action'] : null;

if (empty($action))
	die('Unknown action!');

$APPLICATION->RestartBuffer();
$action = mb_strtoupper($action);
switch ($action)
{
	case 'RELOAD':
		$APPLICATION->ShowAjaxHead();
		$APPLICATION->IncludeComponent('bitrix:crm.activity.call_list',
			'',
			array(
				'ACTION' => 'RELOAD',
				'CALL_LIST_ID' => (int)$_REQUEST['callListId'],
			)
		);
		break;
	case 'GET_ITEMS_GRID':
		$APPLICATION->ShowAjaxHead();
		$APPLICATION->IncludeComponent('bitrix:crm.activity.call_list',
			'',
			array(
				'ACTION' => 'GET_GRID_PAGE',
				'CALL_LIST_ID' => (int)$_REQUEST['callListId'],
				'ALLOW_EDIT' => $_REQUEST['allowEdit'] == 'Y'
			)
		);
		break;
	case 'GET_CALL_LIST':
		$callListId = (int)$_REQUEST['callListId'];
		if($callListId == 0)
			return false;

		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
			$response = $callList->toArray();
			$statusList = $callList->getStatusList();
			foreach ($statusList as $k => $statusRecord)
			{
				$class = '';
				switch ($statusRecord['STATUS_ID'])
				{
					case 'IN_WORK':
						$class = 'im-phone-call-list-in-work-block';
						break;
					case 'SUCCESS':
						$class = 'im-phone-call-list-successful';
						break;
					case 'WRONG_NUMBER':
						$class = 'im-phone-call-list-not-successful';
						break;
					case 'STOP_CALLING':
						$class = 'im-phone-call-list-not-successful';
						break;
				}
				$statusList[$k]['CLASS'] = $class;
			}
			$response['STATUSES'] = $statusList;
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			$response = array(
				'ERROR' => array(
					'CODE' => $e->getCode(),
					'MESSAGE' => $e->getMessage()
				)
			);
		}
		echo \Bitrix\Main\Web\Json::encode($response);

		break;
	case 'GET_AVATAR':
		$entityType = $_REQUEST['entityType'];
		$entityId = $_REQUEST['entityId'];
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.call_list');
		$avatar = CrmActivityCallListComponent::getAvatar($entityType, $entityId, $USER->GetId());
		echo \Bitrix\Main\Web\Json::encode(array(
			'avatar' => $avatar
		));
		break;
	case 'SET_ELEMENT_RANK':
		$callListId = (int)$_REQUEST['parameters']['callListId'];
		$elementId = (int)$_REQUEST['parameters']['elementId'];
		$rank = (int)$_REQUEST['parameters']['rank'];
		if($callListId == 0 || $elementId == 0 || $rank == 0)
			return false;
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return false;
		}
		$result = $callList->setElementRank($elementId, $rank);
		if(!$result)
			return false;

		$callList->persist();
		echo \Bitrix\Main\Web\Json::encode($callList->toArray());

		break;
	case 'SET_ELEMENT_STATUS':
		$callListId = (int)$_REQUEST['parameters']['callListId'];
		$elementId = (int)$_REQUEST['parameters']['elementId'];
		$statusId = (string)$_REQUEST['parameters']['statusId'];
		if($callListId == 0 || $elementId == 0 || $statusId == '')
			return false;

		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return false;
		}
		$result = $callList->setElementStatus($elementId, $statusId);
		if(!$result)
			return false;

		$callList->persist();
		if($callList->getItemsCount(\Bitrix\Crm\CallList\CallList::STATUS_IN_WORK) == 0)
		{
			$callList->completeAssociatedActivity();
		}

		echo \Bitrix\Main\Web\Json::encode($callList->toArray());
		break;
	case 'SET_WEBFORM_RESULT':
		$callListId = (int)$_REQUEST['parameters']['callListId'];
		$elementId = (int)$_REQUEST['parameters']['elementId'];
		$webformResultId = (int)$_REQUEST['parameters']['webformResultId'];
		if($callListId == 0 || $elementId == 0 || $webformResultId == 0)
			return false;

		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return false;
		}

		\Bitrix\Crm\CallList\Internals\CallListItemTable::update(
			array(
				'LIST_ID' => $callListId,
				'ENTITY_TYPE_ID' => $callList->getEntityTypeId(),
				'ELEMENT_ID' => $elementId
			),
			array(
				'WEBFORM_RESULT_ID' => $webformResultId
			)
		);

		break;
	case 'APPLY_ORIGINAL_FILTER':
		$callListId = (int)$_REQUEST['callListId'];
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return false;
		}
		$callList->applyOriginalFilter();
		$response = array(
			'SUCCESS' => true,
			'DATA' => array(
				'LIST_URL' => CCrmOwnerType::GetListUrl($callList->getEntityTypeId())
			)
		);
		echo \Bitrix\Main\Web\Json::encode($response);
		
		break;
	case 'DELETE_ITEMS':
		$callListId = (int)$_REQUEST['callListId'];
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return false;
		}
		$items = $_REQUEST['items'];
		if(is_array($items))
		{
			$callList->deleteItems($items);
			$response = array('SUCCESS' => true);
		}
		else
		{
			$response = array('SUCCESS' => false);
		}
		echo \Bitrix\Main\Web\Json::encode($response);

		break;
	default:
		die('Unknown action!');
		break;
}

CMain::FinalActions();