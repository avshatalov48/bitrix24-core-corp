<?php

namespace Bitrix\Sign\Operation\Member\Reminder;

use Bitrix\Main;
use Bitrix\Sign\Agent\Member\SigningReminderAgent;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\NotifyCalculationService;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Member\Notification\ReminderType;

final class Start implements Contract\Operation
{
	private const SECONDS_IN_MINUTE = 60;
	private readonly MemberRepository $memberRepository;

	public function __construct(
		private readonly Document $document,
		?MemberRepository $memberRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		$hasMembersWithNoneEmptyReminderTypes = $this->memberRepository->existsByDocumentIdWithReminderTypeNotEqual(
			$this->document->id,
			ReminderType::NONE
		);
		if (!$hasMembersWithNoneEmptyReminderTypes)
		{
			return new Main\Result();
		}

		$countMembers = $this->memberRepository->countByDocumentId($this->document->id);
		$intervalBetweenExecAndNextExecTime = (new NotifyCalculationService())->getIntervalsBetweenExecAndNextExecTimeInMinutes($countMembers);
		$minuteIntervalBetweenExecAndNextExecTime = self::SECONDS_IN_MINUTE * $intervalBetweenExecAndNextExecTime;

		$nextExecTime = (new DateTime())->withAddSeconds($minuteIntervalBetweenExecAndNextExecTime);
		$agentId = \CAgent::AddAgent(
			name: SigningReminderAgent::getPlanNextRemindDateAgentName($this->document->id),
			module: 'sign',
			interval: $minuteIntervalBetweenExecAndNextExecTime,
			next_exec: $nextExecTime,
			existError: false,
		);
		if ($agentId === false)
		{
			return (new Main\Result())->addError(new Main\Error("Failed to add reminder planning agent for document: {$this->document->id}"));
		}

		$agentId = \CAgent::AddAgent(
			name: SigningReminderAgent::getNotifyAgentName($this->document->id),
			module: 'sign',
			interval: $minuteIntervalBetweenExecAndNextExecTime,
			next_exec: $nextExecTime,
			existError: false,
		);
		if ($agentId === false)
		{
			return (new Main\Result())->addError(new Main\Error("Failed to add agent for document: {$this->document->id}"));
		}

		return new Main\Result();
	}
}