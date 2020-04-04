<?php

namespace Bitrix\Voximplant\Integration;

use Bitrix\Main\Diag\Helper;
use Bitrix\Main\Loader;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\ConfigTable;
use Bitrix\Voximplant\Model\ExternalLineTable;

/**
 * Class for integration with Push & Pull
 * @package Bitrix\Voximplant\Integration
 * @internal
 */
class Pull
{
	public static function sendInvite($users, $callId)
	{
		$call = CallTable::getByCallId($callId);

		if(!$call)
			return false;

		if($call['CONFIG_ID'] > 0)
			$config = ConfigTable::getRowById($call['CONFIG_ID']);
		else
			$config = array();

		if($call['EXTERNAL_LINE_ID'])
		{
			$externalLine = ExternalLineTable::getRowById($call['EXTERNAL_LINE_ID']);
			$externalNumber = $externalLine ? $externalLine['NUMBER'] : '';
		}

		$phoneTitle = $externalNumber ?: $config['PHONE_NAME'] ?: "";

		$portalCall = $call['PORTAL_USER_ID'] > 0;
		if($portalCall && Loader::includeModule('im'))
		{
			$portalCallData = \CIMContactList::GetUserData(array(
				'ID' => array($call['USER_ID'], $call['PORTAL_USER_ID']),
				'DEPARTMENT' => 'N',
				'HR_PHOTO' => 'Y'
			));
		}
		else
		{
			$portalCallData = array();
		}

		$config = Array(
			"callId" => $call['CALL_ID'],
			"callerId" => $call['CALLER_ID'],
			"lineNumber" => $call['PORTAL_NUMBER'],
			"phoneNumber" => $phoneTitle,
			"chatId" => 0,
			"chat" => array(),
			"portalCall" => $portalCall,
			"portalCallUserId" => $portalCall ? (int)$call['USER_ID'] : 0,
			"portalCallData" => $portalCall ? $portalCallData : array(),
			"config" => \CVoxImplantConfig::getConfigForPopup($callId),
			"CRM" => ($call['CRM'] == 'Y' ? \CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID']) : false),
			"showCrmCard" => ($call['CRM'] == 'Y' && !$portalCall),
			"crmEntityType" => $call['CRM_ENTITY_TYPE'],
			"crmEntityId" => $call['CRM_ENTITY_ID'],
			"crmActivityId" => $call['CRM_ACTIVITY_ID'],
			"crmActivityEditUrl" => \CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']),
			"isCallback" => ($call['INCOMING'] == \CVoxImplantMain::CALL_CALLBACK)
		);

		$callName = $call['CALLER_ID'];
		if (isset($config['CRM']['CONTACT']['NAME']) && strlen($config['CRM']['CONTACT']['NAME']) > 0)
		{
			$callName = $config['CRM']['CONTACT']['NAME'];
		}
		if (isset($config['CRM']['COMPANY']) && strlen($config['CRM']['COMPANY']) > 0)
		{
			$callName .= ' ('.$config['CRM']['COMPANY'].')';
		}
		else if (isset($config['CRM']['CONTACT']['POST']) && strlen($config['CRM']['CONTACT']['POST']) > 0)
		{
			$callName .= ' ('.$config['CRM']['CONTACT']['POST'].')';
		}

		$push['sub_tag'] = 'VI_CALL_'.$call['CALL_ID'];
		$push['send_immediately'] = 'Y';
		$push['sound'] = 'call.aif';
		$push['advanced_params'] = Array(
			"notificationsToCancel" => array('VI_CALL_'.$call['CALL_ID']),
			"androidHighPriority" => true,
		);
		if ($portalCall)
		{
			$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $portalCallData['users'][$call['PORTAL_USER_ID']]['name']));
		}
		else
		{
			$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $callName));
			$push['message'] = $push['message'].' '.GetMessage('CALL_FOR_NUMBER', Array('#NUMBER#' => $phoneTitle));
		}

		$push['params'] = Array(
			'ACTION' => 'VI_CALL_'.$call['CALL_ID'],
			'PARAMS' => $config
		);

		return self::send('invite', $users, $config, $push);
	}

	public static function sendDefaultLineId($users, $defaultLineId)
	{
		self::send(
			'changeDefaultLineId',
			$users,
			array(
				'defaultLineId' => $defaultLineId,
				'line' => \CVoxImplantConfig::GetLine($defaultLineId)
			),
			array(),
			1
		);
	}

	protected static function send($command, $users, $params, $push, $ttl = 0)
	{
		if(!Loader::includeModule('pull'))
			return false;

		\Bitrix\Pull\Event::add($users, Array(
			'module_id' => 'voximplant',
			'command' => $command,
			'params' => $params,
			'push' => $push,
			'expiry' => $ttl
		));

		return true;
	}
}