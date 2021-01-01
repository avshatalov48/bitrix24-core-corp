<?php
namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\Localization\Loc;

class BurnDownChart
{
	public function prepareIdealBurnDownChartData(float $sumStoryPoints, SprintRanges $sprintRanges): array
	{
		$idealData = [
			[
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_NULL_DAY_LABEL'),
				'idealValue' => $sumStoryPoints
			]
		];

		$averagePointsPerDay = ($sumStoryPoints / count($sprintRanges->getWeekdays()));

		$value = 0;
		foreach ($sprintRanges->getWeekdays() as $dayNumber => $dayTime)
		{
			$value += $averagePointsPerDay;
			$idealData[] = [
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_DAY_LABEL').' '.$dayNumber,
				'idealValue' => round(($sumStoryPoints - $value), 2)
			];
		}

		return $idealData;
	}

	public function prepareRemainBurnDownChartData(
		float $sumStoryPoints,
		SprintRanges $sprintRanges,
		array $completedStoryPointsMap
	): array
	{
		$remainData = [
			[
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_NULL_DAY_LABEL'),
				'remainValue' => $sumStoryPoints
			]
		];

		$deferredStoryPoints = [];
		foreach ($sprintRanges->getAllDays() as $dayNumber => $dayTime)
		{
			if ($previousWeekday = $sprintRanges->getPreviousWeekdayByDayNumber($dayNumber))
			{
				if ($deferredStoryPoints[$previousWeekday] != $completedStoryPointsMap[$dayNumber])
				{
					$deferredStoryPoints[$previousWeekday] += (float) $completedStoryPointsMap[$dayNumber];
				}
			}
		}

		$previousValue = 0;
		foreach ($sprintRanges->getWeekdays() as $dayNumber => $dayTime)
		{
			if (strtotime('today', $dayTime) <= strtotime('today', time()))
			{
				if (isset($deferredStoryPoints[$dayNumber]))
				{
					$remainValue = $deferredStoryPoints[$dayNumber];
				}
				elseif (isset($completedStoryPointsMap[$dayNumber]))
				{
					$remainValue = $completedStoryPointsMap[$dayNumber];
				}
				else
				{
					$remainValue = $previousValue;
				}
				$remainData[] = [
					'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_DAY_LABEL').' '.$dayNumber,
					'remainValue' => ($previousValue && $previousValue < $remainValue ? $previousValue : $remainValue)
				];
				$previousValue = $remainValue;
			}
		}

		return $remainData;
	}
}