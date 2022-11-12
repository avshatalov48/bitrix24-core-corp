<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\Model\CallUserTable;
use Bitrix\Voximplant\Model\IvrActionTable;

class Router
{
	/** @var Call */
	protected $call;
	public function __construct(Call $call)
	{
		$this->call = $call;
	}

	/**
	 * @param array $request
	 * @return Action|false
	 */
	public function getNextAction(array $request = [])
	{
		$this->call->removeAllInvitedUsers();
		$rootNode = $this->call->getExecutionGraph();
		if(!($rootNode instanceof Node))
		{
			$rootNode = $this->buildExecutionGraph($this->call);
			$this->call->updateExecutionGraph($rootNode);
		}

		if(!$rootNode)
		{
			return Action::create(Command::HANGUP, ['CODE' => 500, 'REASON' => 'Could not create call execution graph']);
		}
		$currentNode = $this->getCurrentNode($rootNode);
		if(!$currentNode)
		{
			return Action::create(Command::HANGUP, ['CODE' => 500, 'REASON' => 'No action found']);
		}

		$repeatedRun = ($currentNode->getId() === $this->call->getStage());

		if($repeatedRun)
		{
			$action = $currentNode->getNextAction($this->call, $request);
			// execution graph could be updated in process
			$this->call->updateExecutionGraph($rootNode);
			if($action)
			{
				$this->updateCallStateWithAction($action);
				return $action;
			}
		}

		$seenNodes = [];
		while($currentNode = $currentNode->getNext())
		{
			if($seenNodes[$currentNode->getId()])
			{
				break;
			}

			$seenNodes[$currentNode->getId()] = true;
			$this->call->updateStage($currentNode->getId());
			$action = $currentNode->getFirstAction($this->call);
			// execution graph could be updated in process
			$this->call->updateExecutionGraph($rootNode);
			if($action)
			{
				$this->updateCallStateWithAction($action);
				return $action;
			}
		}
		return Action::create(Command::HANGUP, ['CODE' => 500, 'REASON' => 'No action found']);
	}

	/**
	 * @return Node|false
	 */
	protected function getCurrentNode(Node $executionRoot)
	{
		if($this->call->getStage() == '')
		{
			return $executionRoot;
		}

		$currentStage = $executionRoot;

		$seenIds = [];

		do
		{
			if($seenIds[$currentStage->getId()])
			{
				return false;
			}
			$seenIds[$currentStage->getId()] = true;

			if($currentStage->getId() === $this->call->getStage())
			{
				return $currentStage;
			}
		} while($currentStage = $currentStage->getNext());

		return false;
	}

	public function buildExecutionGraph(Call $call)
	{
		if($call->getIncoming() == \CVoxImplantMain::CALL_OUTGOING)
		{
			return $this->buildOutgoingExecutionGraph($call);
		}
		else if($call->getIncoming() == \CVoxImplantMain::CALL_INCOMING)
		{
			return $this->buildIncomingExecutionGraph($call);
		}
		else if($call->getIncoming() == \CVoxImplantMain::CALL_CALLBACK)
		{
			return $this->buildCallbackExecutionGraph($call);
		}

		return false;
	}

	/**
	 * @param Call $call
	 * @return Root
	 */
	protected function buildOutgoingExecutionGraph(Call $call)
	{
		$rootNode = new Root();
		$lastNode = $rootNode;

		$phoneNumber = $call->getCallerId();

		if ($phoneNumber === '')
		{
			$rootNode->setNext(new Hangup(404, 'Not found'));
			return $rootNode;
		}

		$queueId = 0;
		$userId = 0;

		if(mb_strpos($phoneNumber, 'queue:') === 0)
		{
			$queueId = (int)mb_substr($phoneNumber, 6);
		}
		else if(mb_strpos($phoneNumber, 'user:') === 0)
		{
			$userId = (int)mb_substr($phoneNumber, 5);
		}
		else
		{
			$entityInfo = \CVoxImplantIncoming::getByInternalPhoneNumber($phoneNumber);
			if($entityInfo)
			{
				if ($entityInfo['ENTITY_TYPE'] === 'user')
				{
					$userId = $entityInfo['ENTITY_ID'];
				}
				else
				{
					$queueId = $entityInfo['ENTITY_ID'];
				}
			}
		}

		if($userId)
		{
			list($userNode, $nextNode) = static::buildUserGraph($userId, 'direct', \CVoxImplantIncoming::RULE_HUNGUP);
			$lastNode->setNext($userNode);
		}
		else if($queueId)
		{
			$queueNode = static::buildQueueGraph($queueId, false);
			if($queueNode instanceof Node)
			{
				$lastNode->setNext($queueNode);
			}
		}
		else
		{
			$securityNode = new SecurityCheck();
			$lastNode->setNext($securityNode);

			$pstnNode = new Pstn($phoneNumber, \CVoxImplantIncoming::RULE_HUNGUP);
			$securityNode->setNext($pstnNode);
		}

		return $rootNode;
	}

	/**
	 * @param Call $call
	 * @return Node | false
	 */
	protected function buildIncomingExecutionGraph(Call $call)
	{
		$config = $call->getConfig();
		$rootNode = new Root();
		$lastNode = $rootNode;

		if($config['USE_SIP_TO'] === 'Y')
		{
			$sipTo = $call->getSipHeader('To');

			if(preg_match('/^sip:(\d+)@/', $sipTo, $matches))
			{
				$extension = $matches[1];
				$entityInfo = \CVoxImplantIncoming::getByInternalPhoneNumber($extension);
				if ($entityInfo)
				{
					if ($entityInfo['ENTITY_TYPE'] === 'user')
					{
						[$sipNode, $nextNode] = static::buildUserGraph($entityInfo['ENTITY_ID'],'sip_to',\CVoxImplantIncoming::RULE_QUEUE);
						$lastNode->setNext($sipNode);
						$lastNode = $nextNode;
					}
					else
					{
						$queueNode = static::buildQueueGraph($entityInfo['ENTITY_ID'],$config['TIMEMAN'] === 'Y');
						$lastNode->setNext($queueNode);
						$lastNode = $queueNode;
					}
				}
			}
		}

		if($config['DIRECT_CODE'] === 'Y')
		{
			$gatheredDigits = $call->getGatheredDigits();
			if($gatheredDigits)
			{

				$entityInfo = \CVoxImplantIncoming::getByInternalPhoneNumber($gatheredDigits);
				if ($entityInfo)
				{
					if ($entityInfo['ENTITY_TYPE'] === 'user')
					{
						[$directNode, $nextNode] = static::buildUserGraph(
							$entityInfo['ENTITY_ID'],
							'direct',
							$config['DIRECT_CODE_RULE'],
							true
						);
						$lastNode->setNext($directNode);
						$lastNode = $nextNode;
					}
					else
					{
						$queueNode = static::buildQueueGraph(
							$entityInfo['ENTITY_ID'],
							$config['TIMEMAN'] === 'Y'
						);
						$lastNode->setNext($queueNode);
						$lastNode = $queueNode;
					}
				}
				else
				{
					$lastNode->setNext(new Hangup(404, 'Could not find user or queue with extension number ' . $this->call->getGatheredDigits()));
					return $rootNode;
				}
			}
		}

		if($config['IVR'] === 'Y' && $config['IVR_ID'] > 0)
		{
			$ivrNode = new Ivr($config['IVR_ID']);
			$lastNode->setNext($ivrNode);
			$lastNode = $ivrNode;
		}

		if($call->getIvrActionId() > 0)
		{
			$ivrActionNode = $this->buildIvrActionGraph($call->getIvrActionId());
			$lastNode->setNext($ivrActionNode);
			$lastNode = $ivrActionNode;
		}
		else
		{
			if($config['CRM_FORWARD'] === 'Y')
			{
				$responsibleId = \CVoxImplantCrmHelper::getResponsibleWithCall($this->call);
				if($responsibleId)
				{
					if($config['TIMEMAN'] != 'Y' || \CVoxImplantUser::GetActiveStatusByTimeman($responsibleId))
					{
						list($crmNode, $nextNode) = $this->buildUserGraph($responsibleId, 'crm', $config['CRM_RULE'], true);
						$lastNode->setNext($crmNode);
						$lastNode = $nextNode;
					}
				}
			}

			if($call->getQueueId())
			{
				$queueNode = $this->buildQueueGraph($call->getQueueId(), $config['TIMEMAN'] === 'Y');
				if($queueNode instanceof Node)
				{
					$lastNode->setNext($queueNode);
				}
			}
		}

		return $rootNode;
	}

	protected function buildCallbackExecutionGraph(Call $call)
	{
		$config = $call->getConfig();
		$rootNode = new Root();
		$lastNode = $rootNode;

		if($config['CRM_FORWARD'] === 'Y')
		{
			$responsibleId = \CVoxImplantCrmHelper::getResponsibleWithCall($this->call);
			if($responsibleId)
			{
				if($config['TIMEMAN'] != 'Y' || \CVoxImplantUser::GetActiveStatusByTimeman($responsibleId))
				{
					list($crmNode, $nextNode) = $this->buildUserGraph($responsibleId, 'crm', $config['CRM_RULE'], true);
					$lastNode->setNext($crmNode);
					$lastNode = $nextNode;
				}
			}
		}

		if($call->getQueueId())
		{
			$queueNode = $this->buildQueueGraph($call->getQueueId(), $config['TIMEMAN'] === 'Y');
			if($queueNode instanceof Node)
			{
				$lastNode->setNext($queueNode);
			}
		}

		return $rootNode;
	}

	public static function buildUserGraph($userId, $connectType, $skipRule, $passIfBusy = false)
	{
		$firstNode = new User($userId, $connectType, $skipRule, $passIfBusy);
		$lastNode = $firstNode;

		if($skipRule == \CVoxImplantIncoming::RULE_PSTN)
		{
			$userPhone = \CVoxImplantPhone::GetUserPhone($userId);
			if($userPhone)
			{
				$lastNode = new Pstn(\CVoxImplantPhone::stripLetters($userPhone), \CVoxImplantIncoming::RULE_VOICEMAIL, $userId);
			}
			else
			{
				$lastNode = new Voicemail($userId, 'User '. $userId .' have no phone number in the profile');
			}
		}
		else if($skipRule == \CVoxImplantIncoming::RULE_QUEUE)
		{
			// move to the next node
		}
		else if($skipRule == \CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$lastNode = new Voicemail($userId, 'Call skipped by rule voicemail. Connect type is ' . $connectType);;
		}
		else
		{
			$lastNode = new Hangup('480', 'Unknown rule ' . $skipRule);
		}

		if($lastNode !== $firstNode)
		{
			$firstNode->setNext($lastNode);
		}

		return [$firstNode, $lastNode];
	}

	/**
	 * @param int $queueId
	 * @param bool $checkTimeman
	 * @return Node
	 */
	public static function buildQueueGraph($queueId, $checkTimeman)
	{
		$queues = [];

		$currentQueueId = $queueId;
		/** @var Queue $previousNode*/
		$previousNode = null;
		while (true)
		{
			if(isset($queues[$currentQueueId]))
			{
				$previousNode->setNext($queues[$currentQueueId]);
				break;
			}

			$queue = \Bitrix\Voximplant\Queue::createWithId($currentQueueId);
			if(!$queue)
			{
				break;
			}

			$queues[$currentQueueId] = new Queue($currentQueueId, $checkTimeman);
			if($previousNode)
			{
				$previousNode->setNext($queues[$currentQueueId]);
			}

			if($queue->getNoAnswerRule() === \CVoxImplantIncoming::RULE_NEXT_QUEUE)
			{
				$previousNode = $queues[$currentQueueId];
				$currentQueueId = $queue->getNextQueueId();
			}
			else
			{
				break;
			}
		}

		return $queues[$queueId];
	}

	/**
	 * @param int $ivrActionId Id of the action, with which ivr was finished.
	 * @return Node
	 */
	protected function buildIvrActionGraph($ivrActionId)
	{
		$config = $this->call->getConfig();
		$root = new IvrAction($ivrActionId);
		$lastNode = $root;

		$action = IvrActionTable::getRowById($ivrActionId);

		if($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_QUEUE)
		{
			$queueId = $action['PARAMETERS']['QUEUE_ID'];
			$queueNode = $this->buildQueueGraph($queueId, $config['TIMEMAN'] === 'Y');
			if($queueNode instanceof Node)
			{
				$lastNode->setNext($queueNode);
			}
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_USER)
		{
			$userId = $action['PARAMETERS']['USER_ID'];
			list($userNode, $nextNode) = $this->buildUserGraph($userId, 'ivr', 'voicemail');
			$lastNode->setNext($userNode);
			$lastNode = $nextNode;
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_PHONE)
		{
			$phoneNumber = $action['PARAMETERS']['PHONE_NUMBER'];
			$pstnNode = new Pstn(\CVoxImplantPhone::stripLetters($phoneNumber), 'voicemail');
			$lastNode->setNext($pstnNode);
		}
		else if ($action['ACTION'] === \Bitrix\Voximplant\Ivr\Action::ACTION_DIRECT_CODE)
		{
			$entityInfo = \CVoxImplantIncoming::getByInternalPhoneNumber($this->call->getGatheredDigits());

			if($entityInfo)
			{
				if ($entityInfo['ENTITY_TYPE'] === 'user')
				{
					list($directNode, $nextNode) = static::buildUserGraph($entityInfo['ENTITY_ID'], 'direct', 'voicemail');
					$lastNode->setNext($directNode);
					$lastNode = $nextNode;
				}
				else
				{
					$queueNode = static::buildQueueGraph($entityInfo['ENTITY_ID'], $config['TIMEMAN' === 'Y']);
					$lastNode->setNext($queueNode);
					$lastNode = $queueNode;
				}
			}
			else
			{
				return new Hangup(404, 'Could not find user with extension number ' . $this->call->getGatheredDigits());
			}
		}
		return $root;
	}

	protected function updateCallStateWithAction(Action $action)
	{
		$firstUserId = 0;
		if($action->getCommand() === Command::INVITE)
		{
			$userIds = [];
			foreach($action->getParameter('USERS') as $userFields)
			{
				$userIds[] = $userFields['USER_ID'];
			}
			if(!empty($userIds))
			{
				$this->call->addUsers($userIds, CallUserTable::ROLE_CALLEE, CallUserTable::STATUS_INVITING);
				$firstUserId = (int)$userIds[0];
			}
		}
		else if($action->getCommand() === Command::PSTN)
		{
			$firstUserId = (int)$action->getParameter('USER_ID');
			if($firstUserId > 0)
			{
				$this->call->addUsers([$firstUserId], CallUserTable::ROLE_CALLEE, CallUserTable::STATUS_INVITING);
			}
		}
		else if($action->getCommand() == Command::VOICEMAIL)
		{
			$firstUserId = (int)$action->getParameter('USER_ID');
		}
		else if($action->getCommand() == Command::ENQUEUE)
		{
			$firstUserId = (int)$action->getParameter('USER_ID');
		}

		if($firstUserId > 0)
		{
			if(in_array($this->call->getIncoming(), [\CVoxImplantMain::CALL_INCOMING, \CVoxImplantMain::CALL_INCOMING_REDIRECT, \CVoxImplantMain::CALL_CALLBACK]))
			{
				$this->call->updateUserId($firstUserId);
			}

			if(\CVoxImplantCrmHelper::shouldCreateLead($this->call))
			{
				\CVoxImplantCrmHelper::registerCallInCrm($this->call);
				if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
				{
					\CVoxImplantCrmHelper::StartCallTrigger($this->call, true);
				}
			}
		}
	}
}