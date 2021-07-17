<?php
namespace Bitrix\Tasks\TourGuide;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\LogTable;
use Bitrix\Tasks\TourGuide;

Loc::loadMessages(__FILE__);

/**
 * Class FirstGridTaskCreation
 *
 * @package Bitrix\Tasks\TourGuide
 */
class FirstGridTaskCreation extends TourGuide
{
	public static $instance;

	protected const OPTION_NAME = 'firstGridTaskCreation';

	private const MIN_CREATED_TASKS_COUNT = 5;

	public function proceed(): bool
	{
		if ($this->isFinished() || $this->isInLocalSession())
		{
			return false;
		}

		if ($this->isMinCreatedTasksCountReached())
		{
			$this->finish();
			return false;
		}

		if (($currentStep = $this->getCurrentStep()) && !$currentStep->isFinished())
		{
			$currentStep->makeTry();

			if ($currentStep->isFinished() && $this->isCurrentStepTheLast())
			{
				$this->finish();
				return true;
			}

			$this->saveToLocalSession();
			$this->saveData();

			return true;
		}

		return false;
	}

	private function isMinCreatedTasksCountReached(): bool
	{
		$query = LogTable::query();
		$query
			->setSelect(['ID'])
			->setLimit(static::MIN_CREATED_TASKS_COUNT)
			->where('USER_ID', $this->getUserId())
			->where('FIELD', 'NEW')
		;
		$count = count($query->exec()->fetchAll());

		return ($count === static::MIN_CREATED_TASKS_COUNT);
	}

	protected function getDefaultSteps(): array
	{
		return [
			[
				'maxTriesCount' => 3,
				'currentTry' => 0,
				'isFinished' => false,
				'additionalData' => [],
			],
		];
	}

	protected function loadPopupData(): array
	{
		$prefix = 'TASKS_TOUR_GUIDE_FIRST_GRID_TASK_CREATION';

		return [
			[
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_0_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_0_TEXT"),
				],
			],
		];
	}
}
