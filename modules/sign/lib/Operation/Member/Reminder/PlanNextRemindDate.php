<?php

namespace Bitrix\Sign\Operation\Member\Reminder;

use Bitrix\Main;
use Bitrix\Sign\Config;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\ProviderCodeService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Member\Notification\ReminderType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Bitrix\Sign\Type\ProviderCode;

final class PlanNextRemindDate implements Contract\Operation
{
	private const REMIND_SENDING_DURATION = '3 days';
	public const INTERVAL_BETWEEN_SIGNING_START_AND_FIRST_NOTIFICATION = '+2 hours';

	private readonly MemberRepository $memberRepository;
	private readonly MemberService $memberService;
	private readonly ProviderCodeService $providerCodeService;
	private readonly UserService $userService;
	private readonly Config\Reminder $reminderConfig;

	public function __construct(
		private readonly Item\Document $document,
		private readonly int $memberPlanLimit,
		private readonly DateTime $currentDateTime = new DateTime(),
		?MemberRepository $memberRepository = null,
		?MemberService $memberService = null,
		?ProviderCodeService $providerCodeService = null,
		?UserService $userService = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->providerCodeService = $providerCodeService ?? Container::instance()->getProviderCodeService();
		$this->userService = $userService ?? Container::instance()->getUserService();
		$this->reminderConfig = Config\Reminder::instance();
	}

	public function launch(): Main\Result
	{
		$document = $this->document;
		if ($document->id === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document ID is not set'));
		}
		if (DocumentStatus::isFinalByDocument($document))
		{
			return new Main\Result();
		}

		$members = $this->listMembersToPlanRemind($document);
		$result = new Main\Result();
		foreach ($members as $member)
		{
			$updatePlannedNextSendDateResult = $this->updatePlannedNextSendDate($document, $member);
			$result->addErrors($updatePlannedNextSendDateResult->getErrors());
		}

		return $result;
	}

	private function updatePlannedNextSendDate(Item\Document $document, Item\Member $member): Main\Result
	{
		$reminderStartDate = DateTime::createFromMainDateTime($member->reminder->startDate);
		$memberTimezoneOffset = $this->getMemberTimezoneOffset($member);
		$finalReminderSendDateInMemberTimezone = $reminderStartDate
			->withAddSeconds($memberTimezoneOffset)
			->withAdd(self::REMIND_SENDING_DURATION)
			->withTime(23, 59, 59)
		;
		$result = $this->providerCodeService->loadByDocument($document);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$planedNextSendDateInMemberTimezone = $this->calculatePlanedNextSendDateInMemberDate($member);
		if (
			$planedNextSendDateInMemberTimezone >= $finalReminderSendDateInMemberTimezone
			|| MemberStatus::isFinishForSigning($member->status)
		)
		{
			$member->reminder->completed = true;
		}
		elseif (
			$member->role === Role::ASSIGNEE
			&& !$this->needToSendRemindersToAssignee($member, $document)
		)
		{
			$member->reminder->completed = true;
		}
		else
		{
			$plannedNextSendDateInServerTimeZone = $planedNextSendDateInMemberTimezone->withAddSeconds(
				-$memberTimezoneOffset,
			);
			$member->reminder->plannedNextSendDate = $plannedNextSendDateInServerTimeZone;
		}

		return $this->memberRepository->update($member);
	}

	private function getPlanedNextDaysReminder(Item\Member $member): DateTime
	{
		$firstIntervalStart = $this->getPeriodStartAndEndByMember($member)->getWithMinimalStartDate()->start;
		$dayOfWeek = $firstIntervalStart->getDayOfWeek();
		// skip weekends
		if (in_array($dayOfWeek, range(1, 4), true))
		{
			$nextDayFirstReminderSendingDate = $firstIntervalStart->withAdd('+1 day');
		}
		else
		{
			$nextDayFirstReminderSendingDate = $this->getNextMondayAfter($firstIntervalStart);
		}

		return $nextDayFirstReminderSendingDate;
	}

	private function getNextMondayAfter(DateTime $date): DateTime
	{
		$dayOfWeek = $date->getDayOfWeek();

		if ($dayOfWeek == 1)
		{
			return $date->clone();
		}
		$daysToAdd = 8 - $dayOfWeek;

		return $date->withAdd("{$daysToAdd} days");
	}

	private function calculatePlanedNextSendDateInMemberDate(Item\Member $member): DateTime
	{
		$periodsStartAndEnd = $this->getPeriodStartAndEndByMember($member);

		$itFirstReminderSending = $member->reminder->lastSendDate === null;
		if ($itFirstReminderSending)
		{
			return $this->getFirstReminderSendingPlannedDate($member);
		}
		$memberTimezoneOffset = $this->getMemberTimezoneOffset($member);
		$lastSendDate = DateTime::createFromMainDateTime($member->reminder->lastSendDate)
			->withAddSeconds($memberTimezoneOffset)
		;

		$memberCurrentDateTime = $this->currentDateTime->withAddSeconds($memberTimezoneOffset);
		foreach ($periodsStartAndEnd as $period)
		{
			if ($lastSendDate > $period->start || $period->end < $memberCurrentDateTime)
			{
				continue;
			}

			return $period->start;
		}

		return $this->getPlanedNextDaysReminder($member);
	}

	private function getNextPeriodStart(Item\Member $member, DateTime $dateTime): ?DateTime
	{
		$nextPeriodStart = null;
		foreach ($this->getPeriodStartAndEndByMember($member) as $period)
		{
			if ($period->start > $dateTime)
			{
				$nextPeriodStart = $period->start;
				break;
			}
		}

		return $nextPeriodStart;
	}

	private function listMembersToPlanRemind(Item\Document $document): Item\MemberCollection
	{
		$reminderWithNotPlannedNextSendDateFilter = Main\ORM\Query\Query::filter()
			->where(
				Main\ORM\Query\Query::filter()
					->logic('or')
					->whereNull('REMINDER_PLANNED_NEXT_SEND_DATE')
					->whereColumn("REMINDER_PLANNED_NEXT_SEND_DATE", "<=", "REMINDER_LAST_SEND_DATE")
			)
			->whereNotNull('REMINDER_START_DATE')
			->whereNot('REMINDER_TYPE', ReminderType::NONE->toInt())
			->where('REMINDER_COMPLETED', false)
		;

		return $this->memberRepository->listByDocumentIdAndMemberStatusesAndCustomFilter(
			$document->id,
			MemberStatus::getReadyForSigning(),
			$reminderWithNotPlannedNextSendDateFilter,
			$this->memberPlanLimit,
		);
	}

	/**
	 * @return array<value-of<ReminderType>, Item\DateTime\DateIntervalCollection>
	 */
	private function getMemberTodayPeriodsByType(Item\Member $member): array
	{
		$memberTimezoneOffset = $this->getMemberTimezoneOffset($member);
		$memberCurrentDate = $this->currentDateTime->withAddSeconds($memberTimezoneOffset);
		if ($this->reminderConfig->usedCustomReminderPeriods())
		{
			return $this->reminderConfig->getCustomIntervalsDateIntervals();
		}

		$firstIntervalStart = $memberCurrentDate->withTime(8, 0);
		$firstIntervalEnd = $memberCurrentDate->withTime(11, 59, 59, 999999);
		$secondIntervalStart = $memberCurrentDate->withTime(12, 0);
		$secondIntervalEnd = $memberCurrentDate->withTime(15, 59, 59, 999999);
		$thirdIntervalStart = $memberCurrentDate->withTime(16, 0);
		$thirdIntervalEnd = $memberCurrentDate->withTime(19, 0);

		$firstPeriod = new Item\DateTime\DateInterval($firstIntervalStart, $firstIntervalEnd);
		$secondPeriod = new Item\DateTime\DateInterval($secondIntervalStart, $secondIntervalEnd);
		$thirdPeriod = new Item\DateTime\DateInterval($thirdIntervalStart, $thirdIntervalEnd);

		return [
			ReminderType::ONCE_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstPeriod,
			),
			ReminderType::TWICE_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstPeriod,
				$secondPeriod,
			),
			ReminderType::THREE_TIMES_PER_DAY->value => new Item\DateTime\DateIntervalCollection(
				$firstPeriod,
				$secondPeriod,
				$thirdPeriod,
			),
		];
	}

	/**
	 * @param ReminderType $reminderType
	 *
	 * @return Item\DateTime\DateIntervalCollection
	 */
	private function getPeriodStartAndEndByMember(Item\Member $member): Item\DateTime\DateIntervalCollection
	{
		$periodsByType = $this->getMemberTodayPeriodsByType($member);

		return $periodsByType[$member->reminder->type->value] ?? new Item\DateTime\DateIntervalCollection();
	}

	private function getFirstReminderSendingPlannedDate(Item\Member $member): DateTime
	{
		$periodsStartAndEnd = $this->getPeriodStartAndEndByMember($member);

		$reminderStartDate = DateTime::createFromMainDateTime($member->reminder->startDate);
		$reminderStartDateInMemberTimeZone = $reminderStartDate->withAddSeconds(
			$this->getMemberTimezoneOffset($member),
		);
		$nextSendingDateInMemberTimeZone = $reminderStartDateInMemberTimeZone->withAdd(
			self::INTERVAL_BETWEEN_SIGNING_START_AND_FIRST_NOTIFICATION,
		);

		foreach ($periodsStartAndEnd as $period)
		{
			if (
				$period->isIncludedInclusively($reminderStartDateInMemberTimeZone)
				&& $period->isIncludedInclusively($nextSendingDateInMemberTimeZone)
			)
			{
				$nextPeriodStartDate = $this->getNextPeriodStart($member, $period->end);
				if ($nextPeriodStartDate === null)
				{
					continue;
				}

				return $nextPeriodStartDate;
			}

			if ($period->isIncludedInclusively($nextSendingDateInMemberTimeZone))
			{
				return $nextSendingDateInMemberTimeZone;
			}

			if ($period->start > $nextSendingDateInMemberTimeZone)
			{
				return $period->start;
			}
		}

		return $this->getPlanedNextDaysReminder($member);
	}

	private function getMemberTimezoneOffset(Item\Member $member): int
	{
		$memberUserId = $this->memberService->getUserIdForMember($member, $this->document);

		return $memberUserId === null ? 0 : $this->userService->getUserTimezoneOffsetRelativeToServer($memberUserId, true);
	}

	private function needToSendRemindersToAssignee(Item\Member $member, Item\Document $document): bool
	{
		if (MemberStatus::isFinishForSigning($member->status))
		{
			return false;
		}

		if ($document->initiatedByType === InitiatedByType::EMPLOYEE)
		{
			return true;
		}

		$isWaitSignersExist = $this->memberRepository->existsByDocumentIdWithRoleAndStatus(
			$member->documentId,
			Role::SIGNER,
			MemberStatus::WAIT,
		);

		return $isWaitSignersExist;
	}
}