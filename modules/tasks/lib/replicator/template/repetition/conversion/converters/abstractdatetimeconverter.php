<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

abstract class AbstractDateTimeConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields = [];
		$template = $repository->getEntity();
		if($template->getDeadlineAfter() > 0)
		{
			$taskFields['DEADLINE'] = $this->getNewDate($template->getDeadlineAfter(), $template->getMatchWorkTime());
		}

		if($template->getStartDatePlanAfter() > 0)
		{
			$taskFields['START_DATE_PLAN'] = $this->getNewDate($template->getStartDatePlanAfter(), $template->getMatchWorkTime());
		}

		if($template->getEndDatePlanAfter() > 0)
		{
			$taskFields['END_DATE_PLAN'] = $this->getNewDate($template->getEndDatePlanAfter(), $template->getMatchWorkTime());
		}

		return $taskFields;
	}

	private function getNewDate(int $seconds, bool $matchWorkTime): ?DateTime
	{
		if (!$seconds)
		{
			return null;
		}

		if ($matchWorkTime)
		{
			return $this->getDateMatchedWorkTime($seconds);
		}

		return $this->getDateAfter($seconds);
	}

	private function getDateMatchedWorkTime(int $seconds): DateTime
	{
		$dateInst = DateTime::createFromUserTimeGmt(new DateTime());
		$calendar = Calendar::getInstance();

		$nextDate = $calendar->calculateEndDate($dateInst, $seconds);
		$clone = clone $nextDate;

		if (!$calendar->isWorkTime($clone))
		{
			$nextDate = $calendar->getClosestWorkTime($clone);
		}

		$nextDate = $nextDate->convertToLocalTime()->getTimestamp();

		return DateTime::createFromTimestamp($nextDate - User::getTimeZoneOffsetCurrentUser());
	}

	private function getDateAfter(int $seconds): DateTime
	{
		$then = new DateTime();
		$then->add('T'.$seconds.'S');
		$then->stripSeconds();

		return $then;
	}

	abstract public function getTemplateFieldName(): string;
}