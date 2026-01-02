<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Agent;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\BookingVisitStatus;
use Bitrix\Booking\Internals\Service\Time;

class ComingSoonBookingAgentManager
{
	private const MODULE_ID = 'booking';
	private const COMING_SOON_REMIND_MIN = 15;

	public function schedule(Booking $booking): void
	{
		if (!$this->shouldSchedule($booking))
		{
			return;
		}

		$agentName = $this->getAgentName($booking);
		$nextExec = $this->calculateExecuteTime($booking);

		\CAgent::AddAgent(
			name: $agentName,
			module: self::MODULE_ID,
			interval: 0,
			next_exec: $nextExec,
			existError: false
		);
	}

	public function unSchedule(Booking $booking): void
	{
		$agentName = $this->getAgentName($booking);

		\CAgent::RemoveAgent(name: $agentName, module: self::MODULE_ID);
	}

	public function updateSchedule(Booking $prevBookingState, Booking $newBookingState): void
	{
		// if there is no changes which lead to reschedule
		$shouldReschedule = $this->shouldReschedule($newBookingState, $prevBookingState);
		if (!$shouldReschedule)
		{
			return;
		}

		// if new state not satisfy schedule conditions, need to remove previous agent
		$shouldSchedule = $this->shouldSchedule($newBookingState);
		if (!$shouldSchedule)
		{
			$this->unSchedule($prevBookingState);

			return;
		}

		// try to reschedule by agent update, if somehow there is no success,
		// try to reschedule by remove and add new agent
		if ($this->reSchedule($newBookingState, $prevBookingState))
		{
			return;
		}

		$this->unSchedule($prevBookingState);
		$this->schedule($newBookingState);
	}

	private function reSchedule(Booking $newBookingState, Booking $prevBookingState = null): bool
	{
		$agentName = $this->getAgentName($newBookingState);
		$nextExec = $this->calculateExecuteTime($newBookingState);

		return $this->updateAgent($agentName, $nextExec);
	}

	/**
	 * Check if booking state satisfy schedule conditions.
	 */
	private function shouldSchedule(Booking $newBookingState): bool
	{
		return $newBookingState->getDatePeriod()?->getDateFrom()->getTimestamp() > time()
			&& in_array($newBookingState->getVisitStatus(), [
				BookingVisitStatus::Unknown,
				BookingVisitStatus::NotVisited,
			], true)
		;
	}

	/**
	 * Check if booking state changes lead to rescheduling.
	 * But it may not satisfy schedule conditions.
	 */
	private function shouldReschedule(Booking $newBookingState, Booking $prevBookingState): bool
	{
		//TODO: recurring bookings are not supported
		$prevStartTime = $prevBookingState->getDatePeriod()?->getDateFrom()->getTimestamp();
		$newStartTime = $newBookingState->getDatePeriod()?->getDateFrom()->getTimestamp();
		$bookingStartChanged = $prevStartTime !== $newStartTime;

		if (!$bookingStartChanged)
		{
			return false;
		}

		// check if time changes lead to immediately agent trigger
		$now = time();
		$prevTriggerTime = $prevStartTime - (self::COMING_SOON_REMIND_MIN * Time::SECONDS_IN_MINUTE);
		// check if this is trigger time for previous booking state
		$alreadyTriggeredRightBefore = $prevTriggerTime < $now && $now < $prevStartTime;
		$newTriggerTime = $newStartTime - (self::COMING_SOON_REMIND_MIN * Time::SECONDS_IN_MINUTE);
		// check if this is trigger time for new booking state
		$shouldTriggerRightNow = $newTriggerTime < $now && $now < $newStartTime;
		if (
			$alreadyTriggeredRightBefore
			&& $shouldTriggerRightNow
		)
		{
			return false;
		}

		return true;
	}

	private function updateAgent(string $agentName, string $execTime): bool
	{
		$agent = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => self::MODULE_ID,
				'=NAME' => $agentName,
			]
		)->Fetch();

		if ($agent === false)
		{
			return false;
		}

		$updateResult = \CAgent::Update(
			(int)$agent['ID'],
			['NEXT_EXEC' => $execTime]
		);

		return (bool)$updateResult;
	}

	private function getAgentName(Booking $booking): string
	{
		return ComingSoonBookingAgent::getName($booking->getId());
	}

	private function calculateExecuteTime(Booking $booking): string
	{
		//TODO: recurring bookings are not supported
		$startTime = $booking->getDatePeriod()?->getDateFrom()->getTimestamp();
		$executeTime = $startTime - (self::COMING_SOON_REMIND_MIN * Time::SECONDS_IN_MINUTE);
		$now = time();
		if ($now > $executeTime)
		{
			$executeTime = $now;
		}

		return \ConvertTimeStamp($executeTime + \CTimeZone::GetOffset(), 'FULL');
	}
}
