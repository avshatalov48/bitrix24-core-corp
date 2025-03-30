<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\EventIntersection;

use Bitrix\Booking\Internals\Service\Recurr;
use Bitrix\Booking\Internals\Service\Rrule;

class RruleWithRrule
{
	private int $iterationsRequired = 0;
	private RruleWithDatePeriod $rruleWithDatePeriod;

	public function __construct()
	{
		$this->rruleWithDatePeriod = new RruleWithDatePeriod();
	}

	public function doIntersect(
		Rrule $rule1,
		Rrule $rule2
	): bool
	{
		$this->iterationsRequired = 0;

		if (!$this->canRulesIntersect($rule1, $rule2))
		{
			return false;
		}

		foreach ($rule2->getDatePeriodsSequence() as $datePeriod)
		{
			$this->iterationsRequired++;

			$doIntersect = $this->rruleWithDatePeriod->doIntersect($rule1, $datePeriod);
			$this->iterationsRequired += $this->rruleWithDatePeriod->getIterationsRequired();

			if ($doIntersect)
			{
				return true;
			}
		}

		return false;
	}

	public function getIterationsRequired(): int
	{
		return $this->iterationsRequired;
	}

	private function canRulesIntersect(Rrule $rule1, Rrule $rule2): bool
	{
		if (RruleWithDatePeriod::doIntersectBasedOnTime($rule1->getDatePeriod(), $rule2->getDatePeriod()) === false)
		{
			return false;
		}

		if ($rule1->getRrule()->getTimezone() === $rule2->getRrule()->getTimezone())
		{
			$rule1ByDay = $rule1->getRrule()->getByDay();
			$rule2ByDay = $rule2->getRrule()->getByDay();

			if (
				!empty($rule1ByDay)
				&& !empty($rule2ByDay)
				&& empty(array_intersect($rule1ByDay, $rule2ByDay))
			)
			{
				return false;
			}
		}

		return true;
	}
}
