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
	const COMMAND_TIMEOUT = 'timeout';
	const COMMAND_ANSWER_SELF = 'answer_self';
	const COMMAND_UPDATE_CRM = 'update_crm';
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

		$push['sub_tag'] = 'VI_CALL_' . $call->getCallId();
		$push['send_immediately'] = 'Y';
		$push['sound'] = 'call.aif';
		$push['advanced_params'] = Array(
			"notificationsToCancel" => array('VI_CALL_'.$call->getCallId()),
			"androidHighPriority" => true,
		);
		if ($call->isInternalCall())
		{
			$push['message'] = Loc::getMessage('INCOMING_CALL', Array('#NAME#' => $portalCallData['users'][$call->getPortalUserId()]['name']));
		}
		else
		{
			$push['message'] = Loc::getMessage('INCOMING_CALL', Array('#NAME#' => $callName));
			$push['message'] = $push['message'].' '.Loc::getMessage('CALL_FOR_NUMBER', Array('#NUMBER#' => $phoneTitle));
		}

		$pushParams = [
			'callId' => $config['callId'],
			'callerId' => $config['callerId'],
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
	 * @param array $users
	 * @param array $additioanalParams
	 */
	public function sendTimeout(array $users, $additionalParams = [])
	{
		$this->send($users,static::COMMAND_TIMEOUT, $additionalParams, $this->getCancelingPush());
	}

	/**
	 * @param int $userId
	 */
	public function sendAnswerSelf($userId)
	{
		$this->send([$userId],static::COMMAND_ANSWER_SELF, [], $this->getCancelingPush());
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
			$this->getCancelingPush()
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

	protected function getCancelingPush()
	{
		return [
			'send_immediately' => 'Y',
			'advanced_params' => [
				"notificationsToCancel" => ['VI_CALL_'.$this->call->getCallId()],
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