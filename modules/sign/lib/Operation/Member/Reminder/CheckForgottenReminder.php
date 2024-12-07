<?php

namespace Bitrix\Sign\Operation\Member\Reminder;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Config\Reminder;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Operation\Member\Reminder\CheckForgottenReminderResult;
use Bitrix\Sign\Service\Container;

final class CheckForgottenReminder implements Contract\Operation
{
	private readonly MemberRepository $memberRepository;

	public function __construct(
		private readonly Document $document,
		?MemberRepository $memberRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function launch(): CheckForgottenReminderResult
	{
		$oldestMember = $this->memberRepository->getHavingOldestReminderByDocumentId($this->document->id);
		if ($oldestMember === null || $oldestMember->reminder->startDate === null)
		{
			return new CheckForgottenReminderResult(true);
		}

		$numOfMinutesBeforeReminderDisabled = Reminder::instance()->getNumOfMinutesBeforeAgentDisabled();
		$interval = $oldestMember->reminder->startDate->getDiff(new DateTime());
		$minutesPassedSinceReminderStart = $interval->i + $interval->h * 60 + $interval->days * 24 * 60;

		return new CheckForgottenReminderResult($minutesPassedSinceReminderStart > $numOfMinutesBeforeReminderDisabled);
	}
}