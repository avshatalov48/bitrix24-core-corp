<?php
namespace Bitrix\Tasks\TourGuide\Internals;

/**
 * Class StepQueue
 *
 * @package Bitrix\Tasks\TourGuide\Internals
 */
class StepQueue
{
	private $steps;

	public function __construct(array $steps = [])
	{
		$this->setSteps($steps);
	}

	public function setSteps(array $steps): void
	{
		$this->steps = [];

		foreach ($steps as $step)
		{
			$this->steps[] = new Step($step);
		}
	}

	public function prepareToSave(): array
	{
		$steps = [];

		foreach ($this->steps as $step)
		{
			/** @var Step $step */
			$steps[] = $step->getData();
		}

		return $steps;
	}

	public function getStepByIndex(int $index): ?Step
	{
		return (count($this->steps) - 1 < $index ? null : $this->steps[$index]);
	}

	public function getLastStepIndex(): int
	{
		$lastStepIndex = count($this->steps) - 1;

		return ($lastStepIndex < 0 ? 0 : $lastStepIndex);
	}

	public function getPopupData(): array
	{
		$popupData = [];

		foreach ($this->steps as $index => $step)
		{
			/** @var Step $step */
			$popupData[$index] = $step->getPopupData();
		}

		return $popupData;
	}

	public function setPopupData(array $popupData): void
	{
		foreach ($this->steps as $index => $step)
		{
			/** @var Step $step */
			$step->setPopupData($popupData[$index]);
		}
	}
}
