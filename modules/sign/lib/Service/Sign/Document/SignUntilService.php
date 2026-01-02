<?php

namespace Bitrix\Sign\Service\Sign\Document;

use Bitrix\Main\Type\DateTime;

final class SignUntilService
{
	private const WARNING_PERIOD = 60 * 60 * 24; // 24h
	private const WARNING_PERIOD_GOSKEY = 60 * 60 * 24; // 24h

	private const SIGNING_LIMIT_MONTH = 3; // 3 months

	private const SIGNING_MIN_MINUTES = 5; // 5 min

	public function calcDefaultSignUntilDate(DateTime $forDate): DateTime
	{
		return (clone $forDate)
			->add('+ ' . $this->getDefaultSigningPeriod()->format('%m') . ' months')
			->add('-1 day') // safety buffer
		;
	}

	public function isSignUntilDateInPast(DateTime $dateSignUntil): bool
	{
		return $dateSignUntil < (new DateTime());
	}

	public function isSignUntilDateReachedWarningPeriod(DateTime $dateSingUntil): bool
	{
		return ($dateSingUntil->getTimestamp() - time()) < self::WARNING_PERIOD;
	}

	public function isSignUntilDateExceedMaxPeriod(DateTime $dateStart, DateTime $dateSignUntil): bool
	{
		return $dateSignUntil > (clone $dateStart)->add('+ ' . $this->getMaxSigningPeriod()->format('%m') . ' months');
	}

	public function isSignUntilDateExceedGoskeyMinimalPeriod(DateTime $dateStart, DateTime $dateSignUntil): bool
	{
		return ($dateSignUntil->getTimestamp() - $dateStart->getTimestamp()) < $this->getGoskeyMinimalPeriod();
	}

	public function getDefaultSigningPeriod(): \DateInterval
	{
		return $this->getMaxSigningPeriod();
	}

	public function getMaxSigningPeriod(): \DateInterval
	{
		$dateStart = new \DateTime();
		$dateEnd = (clone $dateStart)->modify('+ ' . self::SIGNING_LIMIT_MONTH . ' months');
		return $dateStart->diff($dateEnd);
	}

	public function getMinSigningPeriodInMinutes(): int
	{
		return self::SIGNING_MIN_MINUTES;
	}

	private function getGoskeyMinimalPeriod(): int
	{
		return self::WARNING_PERIOD_GOSKEY;
	}
}