<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Pull\Event;
use Bitrix\Voximplant\Model\CallUserTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Model\ExternalLineTable;

class Signaling
{

	protected $call;

	const COMMAND_INVITE = 'invite';
	const COMMAND_OUTGOING = 'outgoing';
	const COMMAND_TIMEOUT = 'timeout';
	const COMMAND_ANSWER_SELF = 'answer_self';
	const COMMAND_UPDATE_CRM = 'update_crm';
	const COMMAND_UPDATE_PORTAL_USER = 'updatePortalUser';
	const COMMAND_REPLACE_CALLERID = 'replaceCallerId';
	const COMMAND_START = 'start';
	const COMMAND_HOLD = 'hold';
	const COMMAND_UNHOLD = 'unhold';
	const COMMAND_COMPLETE_TRANSFER = 'completeTransfer';

	public function __construct(Call $call)
	{
		$this->call = $call;
		if(!Loader::includeModule('pull'))
		{
			throw new SystemException('Push and pull module is required');
		}
	}

	public function sendInvite($users)
	{
		$config = $this->call->getConfig();
		$isTransfer = $this->call->getParentCallId() != '';
		$call = $isTransfer ? Call::load($this->call->getParentCallId()) : $this->call;

		if ($config['PORTAL_MODE'] == \CVoxImplantConfig::MODE_SIP)
		{
			if($call->getExternalLineId())
			{
				$externalLine = ExternalLineTable::getRowById($call->getExternalLineId());
				$externalNumber = $externalLine ? $externalLine['NUMBER'] : '';
			}

			$phoneTitle = $externalNumber ?: $config['PHONE_TITLE'];
		}
		else
		{
			$phoneTitle = $call->getPortalNumber();
		}

		if($call->isInternalCall() && Loader::includeModule('im'))
		{
			$portalCallData = \CIMContactList::GetUserData(array(
				'ID' => array($call->getUserId(), $call->getPortalUserId()),
				'DEPARTMENT' => 'N',
				'HR_PHOTO' => 'Y'
			));
		}
		else
		{
			$portalCallData = array();
		}

		$config = Array(
			"callId" => $call->getCallId(),
			"callerId" => $call->getCallerId(),
			"lineNumber" => $call->getPortalNumber(),
			"companyPhoneNumber" => $phoneTitle,
			"phoneNumber" => $phoneTitle,
			"chatId" => 0,
			"chat" => array(),
			"portalCall" => $call->isInternalCall(),
			"portalCallUserId" => $call->isInternalCall() ? (int)$call->getUserId() : 0,
			"portalCallData" => $call->isInternalCall() ? $portalCallData : [],
			"config" => \CVoxImplantConfig::getConfigForPopup($call->getCallId()),
			"CRM" => ($call->isCrmEnabled() && !$call->isInternalCall()
				? \CVoxImplantCrmHelper::GetDataForPopup($call->getCallId(), $call->getCallerId(), $call->getUserId())
				: false
			),
			"showCrmCard" => ($call->isCrmEnabled() && !$call->isInternalCall()),
			"crmEntityType" => $call->getPrimaryEntityType(),
			"crmEntityId" => $call->getPrimaryEntityId(),
			"isCallback" => ($call->getIncoming() == \CVoxImplantMain::CALL_CALLBACK),
			"isTransfer" => $isTransfer
		);

		$callName = $call->getCallerId();
		if (isset($config['CRM']['CONTACT']['NAME']) && $config['CRM']['CONTACT']['NAME'] <> '')
		{
			$callName = $config['CRM']['CONTACT']['NAME'];
		}
		if (isset($config['CRM']['COMPANY']) && $config['CRM']['COMPANY'] <> '')
		{
			$callName .= ' ('.$config['CRM']['COMPANY'].')';
		}
		else if (isset($config['CRM']['CONTACT']['POST']) && $config['CRM']['CONTACT']['POST'] <> '')
		{
			$callName .= ' ('.$config['CRM']['CONTACT']['POST'].')';
		}

		$push['sub_tag'] = 'VI_CALL_' . $call->getCallId();
		$push['send_immediately'] = 'Y';
		$push['sound'] = 'call.aif';
		$push['advanced_params'] = Array(
			"id" => 'VI_CALL_'.$call->getCallId(),
			"notificationsToCancel" => array('VI_CALL_'.$call->getCallId()),
			"androidHighPriority" => true,
		);
		if ($call->isInternalCall())
		{
			$push['message'] = Loc::getMessage('INCOMING_CALL', Array('#NAME#' => $portalCallData['users'][$call->getUserId()]['name']));
		}
		else
		{
			$push['message'] = Loc::getMessage('INCOMING_CALL', Array('#NAME#' => $callName));
			$push['message'] = $push['message'].' '.Loc::getMessage('CALL_FOR_NUMBER', Array('#NUMBER#' => $phoneTitle));
		}

		$pushParams = [
			'callId' => $config['callId'],
			'callerId' => $config['callerId'],
			'callerName' => $callName,
			'companyPhoneNumber' => $config['companyPhoneNumber'],
			'config' => $config['config'],
			'isCallback' => $config['isCallback'],
			'isTransfer' => $config['isTransfer'],
			'portalCall' => $config['portalCall'],
		];
		if($call->isInternalCall() && is_array($portalCallData['users']))
		{
			$pushParams['portalCallUserId'] = $config['portalCallUserId'];
			$pushParams['portalCallData'] = [
				'users' => []
			];
			foreach ($portalCallData['users'] as $userId => $userFields)
			{
				if(!in_array($userId, $users))
				{
					$pushParams['portalCallData']['users'][$userId] = [
						'name' => $userFields['name'],
						'avatar' => $userFields['avatar'],
					];
				}
			}
		}
		else
		{
			$pushParams['CRM'] = [
				'FOUND' => $config['CRM']['FOUND'],
			];
			if(isset($config['CRM']['CONTACT']))
			{
				$pushParams['CRM']['CONTACT'] = $config['CRM']['CONTACT'];
			}
			if(isset($config['CRM']['COMPANY']))
			{
				$pushParams['CRM']['COMPANY'] = $config['CRM']['COMPANY'];
			}
		}

		$push['params'] = Array(
			'ACTION' => 'VI_CALL_'.$call->getCallId(),
			'PARAMS' => $pushParams
		);

		$this->send($users, static::COMMAND_INVITE, $config, $push);
	}

	/**
	 * @param int $userId
	 * @param string $callDevice
	 */
	public function sendOutgoing(int $userId,$callDevice = 'WEBRTC')
	{
		$call = $this->call;
		$config = $call->getConfig();
		$queueId = $call->getQueueId();
		$queueName = $queueId ? Queue::createWithId($queueId)->getName() : null;

		if ($call->isInternalCall() && Loader::includeModule('im'))
		{
			$userData = \CIMContactList::GetUserData([
				'ID' => $call->getUserIds(),
				'DEPARTMENT' => 'N',
				'HR_PHOTO' => 'Y']
			);
		}
		else
		{
			$userData = [];
		}

		$crmData = $call->isInternalCall() ? [] : \CVoxImplantCrmHelper::GetDataForPopup($call->getCallId(), $call->getCallerId(), $userId);

		$config = [
			'callId' => $call->getCallId(),
			'callDevice' => $callDevice === 'PHONE'? 'PHONE': 'WEBRTC',
			'phoneNumber' => $call->getCallerId(),
			'portalCall' => $call->isInternalCall(),
			'portalCallUserId' => $call->isInternalCall() ? $call->getPortalUserId(): 0,
			'portalCallData' => $call->isInternalCall() ? $userData: [],
			'portalCallQueueName' => $queueName,
			'config' => \CVoxImplantConfig::getConfigForPopup($call->getCallId()),
			'lineNumber' => $call->getPortalNumber() ?: '',
			'lineName' => $config['PORTAL_MODE'] === \CVoxImplantConfig::MODE_SIP ? $config['PHONE_TITLE'] : $config['PHONE_NAME'],
			"CRM" => $crmData,
		];

		if(!$call->isInternalCall())
		{
			$config['showCrmCard'] = ($call->isCrmEnabled());
			$config['crmEntityType'] = $call->getPrimaryEntityType();
			$config['crmEntityId'] = $call->getPrimaryEntityId();
			$config['crmBindings'] = \CVoxImplantCrmHelper::resolveBindingNames($call->getCrmBindings());
		}

		\CVoxImplantHistory::WriteToLog([
			'COMMAND' => 'outgoing',
			'USER_ID' => $userId,
			'CALL_ID' => $call->getId(),
			'CALL_DEVICE' => $callDevice,
			'PHONE_NUMBER' => $call->getCallerId(),
			'PORTAL_CALL_USER_ID' => $call->getPortalUserId(),
			'CRM' => $crmData,
			'CRM_ENTITY_TYPE' => $call->getPrimaryEntityType(),
			'CRM_ENTITY_ID' => $call->getPrimaryEntityId(),
			'CRM_ACTIVITY_ID' => $call->getCrmActivityId(),
		]);

		$this->send([$userId],static::COMMAND_OUTGOING, $config, null);
	}

	/**
	 * @param array $users
	 * @param array $additioanalParams
	 */
	public function sendTimeout(array $users, $additionalParams = [])
	{
		$this->send($users,static::COMMAND_TIMEOUT, $additionalParams, $this->getCancelingPush('_FINISH'));
	}

	/**
	 * @param int $userId
	 */
	public function sendAnswerSelf($userId)
	{
		$this->send([$userId],static::COMMAND_ANSWER_SELF, [], $this->getCancelingPush('_ANSWER'));
	}

	public function sendStart($userId, $device)
	{
		$this->send(
			[$userId],
			static::COMMAND_START,
			[
				'callDevice' => $device,
				'CRM' => \CVoxImplantCrmHelper::GetDataForPopup($this->call->getCallId(), $this->call->getCallerId(), $userId)
			],
			$this->getCancelingPush('_ANSWER')
		);
	}

	public function sendUpdatePortalUser($userId)
	{
		if($this->call->isInternalCall() && Loader::includeModule('im'))
		{
			$portalCallData = \CIMContactList::GetUserData([
				'ID' => [$this->call->getPortalUserId()],
				'DEPARTMENT' => 'N',
				'HR_PHOTO' => 'Y',
			]);
		}
		else
		{
			$portalCallData = [];
		}

		$this->send(
			[$userId],
			static::COMMAND_UPDATE_PORTAL_USER,
			[
				'portalCall' => $this->call->isInternalCall(),
				'portalCallData' => $portalCallData,
				'portalCallUserId' => $this->call->isInternalCall() ? $this->call->getPortalUserId() : 0,
			]
		);
	}

	public function sendUpdateCrm(array $users)
	{
		foreach ($users as $userId)
		{
			$crmData = \CVoxImplantCrmHelper::GetDataForPopup(
				$this->call->getCallId(), $this->call->getCallerId(), $userId
			);

			$params = Array(
				"callId" => $this->call->getCallId(),
				"CRM" => $crmData,

				"showCrmCard" => $this->call->isCrmEnabled(),
				"crmEntityType" => $this->call->getPrimaryEntityType(),
				"crmEntityId" => $this->call->getPrimaryEntityId(),
				"crmActivityId" => $this->call->getCrmActivityId(),
				"crmActivityEditUrl" => \CVoxImplantCrmHelper::getActivityEditUrl($this->call->getCrmActivityId()),
				"crmBindings" => \CVoxImplantCrmHelper::resolveBindingNames($this->call->getCrmBindings())
			);
			$this->send([$userId], static::COMMAND_UPDATE_CRM, $params);
		}
	}

	public function sendReplaceCallerId($userId, $newCallerId)
	{
		$crmData = \CVoxImplantCrmHelper::GetDataForPopup(
			$this->call->getCallId(), $this->call->getCallerId(), $userId
		);

		$this->send([$userId], static::COMMAND_REPLACE_CALLERID, [
			"callerId" => $newCallerId,
			"CRM" => $crmData,
			"showCrmCard" => $this->call->isCrmEnabled(),
			"crmEntityType" => $this->call->getPrimaryEntityType(),
			"crmEntityId" => $this->call->getPrimaryEntityId(),
			"crmBindings" => \CVoxImplantCrmHelper::resolveBindingNames($this->call->getCrmBindings())
		]);
	}

	public function sendHold($userId)
	{
		$this->send([$userId],static::COMMAND_HOLD, []);
	}

	public function sendUnHold($userId)
	{
		$this->send([$userId],static::COMMAND_UNHOLD, []);
	}

	public function sendCompleteTransfer($userId, $newCallId, $device)
	{
		$this->send([$userId], static::COMMAND_COMPLETE_TRANSFER, [
			'newCallId' => $newCallId,
			'callDevice' => $device
		]);
	}

	protected function getCancelingPush($idSuffix = '')
	{
		return [
			'send_immediately' => 'Y',
			'advanced_params' => [
				'id' => 'VI_CALL_' . $this->call->getCallId() . $idSuffix,
				'notificationsToCancel' => ['VI_CALL_' . $this->call->getCallId()],
			]
		];
	}

	protected function send($users, $command, $params, $push = null)
	{
		$params['callId'] = $this->call->getCallId();

		Event::add($users, [
			'module_id' => 'voximplant',
			'command' => $command,
			'params' => $params,
			'push' => $push
		]);
	}

}