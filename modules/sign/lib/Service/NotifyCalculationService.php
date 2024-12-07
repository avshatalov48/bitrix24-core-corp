<?php

namespace Bitrix\Sign\Service;

use Bitrix\Sign\Repository\MemberRepository;

class NotifyCalculationService
{
	private const SECONDS_IN_MINUTE = 60;
	private const INTERVALS_BETWEEN_EXEC_AND_NEXT_EXEC_TIME = [
		10000 => 2,
		7500 => 3,
		5000 => 4,
		2500 => 5,
		0 => 6,
	];
	private readonly MemberRepository $memberRepository;

	public function __construct(
		?MemberRepository $memberRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function getPlanMemberAndSendReminderLimit(int $documentId): int
	{
		$memberRepository = $this->memberRepository;
		$countMembers = $memberRepository->countByDocumentId($documentId);

		$timeBetweenNotify = $this->getIntervalsBetweenExecAndNextExecTimeInMinutes($countMembers);

		$minIntervalOnNotifyInMinutes = 2;

		$hitsOnNotify = (self::SECONDS_IN_MINUTE * $minIntervalOnNotifyInMinutes) / $timeBetweenNotify;
		$minLimitHits = 5;

		if ($hitsOnNotify === 0)
		{
			return $minLimitHits;
		}

		return (int)($countMembers / $hitsOnNotify) + $minLimitHits;
	}

	public function getIntervalsBetweenExecAndNextExecTimeInMinutes(int $countMembers): int
	{
		foreach (self::INTERVALS_BETWEEN_EXEC_AND_NEXT_EXEC_TIME as $memberThreshold => $minutesCount)
		{
			if ($countMembers >= $memberThreshold)
			{
				return $minutesCount;
			}
		}

		return self::INTERVALS_BETWEEN_EXEC_AND_NEXT_EXEC_TIME[0];
	}
}