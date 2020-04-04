<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Model\IvrActionTable;

class IvrAction extends Node
{
	protected $actionId;

	public function __construct($actionId)
	{
		parent::__construct();
		$this->actionId = $actionId;
	}

	public function getFirstAction(Call $call)
	{
		$config = $call->getConfig();

		$action = IvrActionTable::getRowById($this->actionId);

		if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_QUEUE)
		{
			$queueId = $action['PARAMETERS']['QUEUE_ID'];
			$queueNode = Router::buildQueueGraph($queueId, $config['TIMEMAN'] === 'Y');
			if($queueNode instanceof Node)
			{
				$this->setNext($queueNode);
			}
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_USER)
		{
			$userId = $action['PARAMETERS']['USER_ID'];
			list($userNode, $nextNode) = Router::buildUserGraph($userId, 'ivr', 'voicemail');
			$this->setNext($userNode);
			$lastNode = $nextNode;
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_PHONE)
		{
			$phoneNumber = $action['PARAMETERS']['PHONE_NUMBER'];
			$pstnNode = new Pstn(\CVoxImplantPhone::Normalize($phoneNumber, 1), 'voicemail');
			$this->setNext($pstnNode);
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_DIRECT_CODE)
		{
			$userInfo = \CVoxImplantIncoming::getUserByDirectCode($call->getGatheredDigits());

			if ($userInfo)
			{
				list($directNode, $nextNode) = Router::buildUserGraph($userInfo['USER_ID'], 'direct', 'voicemail');
				$this->setNext($directNode);
				$lastNode = $nextNode;
			}
			else
			{
				$this->setNext(new Hangup(404, 'Could not find user with extension number '.$call->getGatheredDigits()));
			}
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_VOICEMAIL)
		{
			$voiceMailNode = new Voicemail($action['PARAMETERS']['USER_ID']);
			$this->setNext($voiceMailNode);
		}
		
		return false;
	}

	public function getNextAction(Call $call, array $request = [])
	{


		return false;
	}
}