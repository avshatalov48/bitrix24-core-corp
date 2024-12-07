<?php
namespace Bitrix\Tasks\TourGuide;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class FirstTimelineTaskCreation
 *
 * @package Bitrix\Tasks\TourGuide
 */
class FirstTimelineTaskCreation extends TourGuide
{
	protected const OPTION_NAME = 'firstTimelineTaskCreation';

	public function proceed(): bool
	{
		if ($this->isFinished() || $this->isInLocalSession())
		{
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

	public function isFirstExperience(): bool
	{
		return true;
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
		$prefix = 'TASKS_TOUR_GUIDE_FIRST_TIMELINE_TASK_CREATION';

		return [
			[
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_0_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_0_TEXT"),
					'article' => 10206524,
				],
				[
					'title' => Loc::getMessage("{$prefix}_POPUP_1_TITLE"),
					'text' => Loc::getMessage("{$prefix}_POPUP_1_TEXT"),
				],
			],
		];
	}
}
