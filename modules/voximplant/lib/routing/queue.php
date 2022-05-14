<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;
use Bitrix\Voximplant\CallQueue;
use Bitrix\Voximplant\Limits;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\Model\CallUserTable;
use Bitrix\Voximplant\Model\QueueUserTable;

class Queue extends Node
{
	protected $queueId;
	protected $queue;
	protected $checkTimeman;

	public function __construct($queueId, $checkTimeman)
	{
		parent::__construct();
		$this->queueId = $queueId;
		$this->queue = \Bitrix\Voximplant\Queue::createWithId($this->queueId);
		$this->checkTimeman = $checkTimeman === true;
	}

	public function getFirstAction(Call $call)
	{
		// clear invite history, possibly left from another queue
		$call->moveToQueue($this->queueId);

		return $this->getNextAction($call);
	}

	public function getNextAction(Call $call, array $request = [])
	{
		if(!$this->queue)
		{
			return new Action(Command::VOICEMAIL, ['REASON' => 'Queue ' . $this->queueId . 'is not found']);
		}

		[$users, $busyUsers] = $this->getUsersToInvite($this->queue, $call->getQueueHistory());

		if(!empty($users))
		{
			$call->addToQueueHistory(array_keys($users));
			$this->queue->touchUser(array_keys($users)[0]);

			return new Action(Command::INVITE, [
				'TYPE_CONNECT' => 'queue',
				'USERS' => array_values($users),
				'QUEUE' => $this->queue->toArray()
			]);
		}

		// could not find users to invite
		if ($this->queue->getNoAnswerRule() == \CVoxImplantIncoming::RULE_QUEUE && (!empty($call->getQueueHistory()) || !empty($busyUsers)))
		{
			$call->clearQueueHistory();

			if (!empty($busyUsers))
			{
				// enqueue call and wait for free user
				return $this->enqueue($call);
			}
			else
			{
				// queue history was not empty, move to the head of the queue
				return $this->getNextAction($call);
			}
		}
		else
		{
			return $this->leaveQueue($call);
		}
	}

	protected function getUsersToInvite(\Bitrix\Voximplant\Queue $queue, array $queueHistory)
	{
		$query = QueueUserTable::query()
			->setSelect([
				'ID',
				'USER_ID',
				'IS_ONLINE' => 'USER.IS_ONLINE',
				'IS_BUSY' => 'USER.IS_BUSY',
				'UF_VI_PHONE' => 'USER.UF_VI_PHONE'
			])
			->where('QUEUE_ID', $this->queueId)
			->where('USER.ACTIVE', 'Y');

		if($queue->getType() == \CVoxImplantConfig::QUEUE_TYPE_EVENLY)
		{
			$query->setOrder(['LAST_ACTIVITY_DATE' => 'asc']);
		}
		else
		{
			$query->setOrder(['ID' => 'asc']);
		}

		if (!empty($queueHistory))
		{
			$query->whereNotIn('USER_ID', $queueHistory);
		}

		$users = [];
		$busyUsers = [];
		$cursor = $query->exec();
		//echo $query->getQuery();
		while ($row = $cursor->fetch())
		{
			$hasMobile = \CVoxImplantUser::hasMobile($row['USER_ID']);

			if ($row['IS_ONLINE'] != 'Y' && $row['UF_VI_PHONE'] != 'Y' && !$hasMobile)
			{
				continue;
			}
			if ($this->checkTimeman && !\CVoxImplantUser::GetActiveStatusByTimeman($row['USER_ID']))
			{
				continue;
			}

			if ($row['IS_BUSY'] == "Y")
			{
				$busyUsers[] = $row['USER_ID'];
				continue;
			}

			$users[$row['USER_ID']] = [
				'USER_ID' => $row['USER_ID'],
				'USER_HAVE_PHONE' => $row['UF_VI_PHONE'] == 'Y'? 'Y': 'N',
				'USER_HAVE_MOBILE' => $hasMobile ? 'Y' : 'N'
			];

			if($queue->getType() != \CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				break;
			}
		}

		return [
			$users,
			$busyUsers
		];
	}

	protected function enqueue(Call $call)
	{
		$call->updateStatus(CallTable::STATUS_ENQUEUED);
		$queuePosition = CallQueue::getCallPosition($call->getCallId());

		$userId = $call->getUserId();
		if ($userId <= 0)
		{
			$userId = $this->queue->getFirstUserId($this->checkTimeman);
			if ($userId)
			{
				$this->queue->touchUser($userId);
			}
		}

		return new Action(Command::ENQUEUE, ['QUEUE_POSITION' => $queuePosition, 'QUEUE_ID' => $this->queueId, 'USER_ID' => $userId]);
	}

	protected function leaveQueue(Call $call)
	{
		$queue = $this->queue;

		if ($queue->getNoAnswerRule() === \CVoxImplantIncoming::RULE_NEXT_QUEUE && Limits::isRedirectToQueueAllowed())
		{
			return false;
		}

		$userId = $call->getUserId();

		if ($userId <= 0)
		{
			$userId = $queue->getFirstUserId($this->checkTimeman);
			if ($userId)
			{
				$queue->touchUser($userId);
			}
		}

		if ($queue->getNoAnswerRule() == \CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
		{
			if ($queue->getForwardNumber() <> '')
			{
				$this->insertAfter(new Pstn($queue->getForwardNumber(),'voicemail'));
				return false;
			}
		}

		if ($queue->getNoAnswerRule() == \CVoxImplantIncoming::RULE_PSTN && $userId > 0)
		{
			$userPhone = \CVoxImplantPhone::GetUserPhone($userId);
			$userPhone = \CVoxImplantPhone::stripLetters($userPhone);
			if ($userPhone)
			{
				$this->insertAfter(new Pstn($userPhone,'voicemail', $userId));
				return false;
			}
		}

		if ($queue->getNoAnswerRule() == \CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			return new Action(Command::VOICEMAIL, ['USER_ID' => $userId]);
		}

		return new Action(Command::HANGUP, ['USER_ID' => $userId]);
	}
}