<?php
namespace Bitrix\Tasks;

use Bitrix\Main\Application;
use Bitrix\Tasks\TourGuide\Internals\Step;
use Bitrix\Tasks\TourGuide\Internals\StepQueue;

/**
 * Class TourGuide
 *
 * @package Bitrix\Tasks
 */
abstract class TourGuide
{
	protected const OPTION_CATEGORY = 'tasks.tour.guides';
	protected const OPTION_NAME = '';

	private $steps;
	private $currentStepIndex = 0;
	private $isFinished = false;
	private $userId;

	abstract public function proceed(): bool;
	abstract protected function getDefaultSteps(): array;
	abstract protected function loadPopupData(): array;

	private function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->steps = new StepQueue();

		$this->loadData();
	}

	protected function loadData($defaultValue = []): void
	{
		$data = \CUserOptions::GetOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME,
			$defaultValue,
			$this->getUserId()
		);

		if (array_key_exists('currentStepIndex', $data))
		{
			$this->currentStepIndex = $data['currentStepIndex'];
		}
		if (array_key_exists('isFinished', $data))
		{
			$this->isFinished = $data['isFinished'];
		}

		$this->setSteps(($data['steps'] ?? []));
		$this->setStepsPopupData();
	}

	private function setSteps(array $steps): void
	{
		$this->steps->setSteps((empty($steps) ? $this->getDefaultSteps() : $steps));
	}

	protected function setStepsPopupData(): void
	{
		$this->steps->setPopupData($this->loadPopupData());
	}

	public function getStepsPopupData(): array
	{
		return $this->steps->getPopupData();
	}

	public function getCurrentStepPopupData(): array
	{
		if ($currentStep = $this->getCurrentStep())
		{
			return $currentStep->getPopupData();
		}

		return [];
	}

	public static function getInstance(int $userId): self
	{
		if (!static::$instance)
		{
			static::$instance = new static($userId);
		}

		return static::$instance;
	}

	protected function saveData(): void
	{
		\CUserOptions::SetOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME,
			$this->prepareDataToSave()
		);
	}

	private function prepareDataToSave(): array
	{
		return [
			'currentStepIndex' => $this->currentStepIndex,
			'isFinished' => $this->isFinished,
			'steps' => $this->steps->prepareToSave(),
		];
	}

	public function finish(): void
	{
		$this->setIsFinished(true);
		$this->getCurrentStep() && $this->getCurrentStep()->finish();
		$this->saveData();
	}

	protected function isInLocalSession(): bool
	{
		if ($application = Application::getInstance())
		{
			$localSession = $application->getLocalSession(static::OPTION_NAME);
			return ($localSession->get('saved') === 'Y');
		}

		return false;
	}

	protected function saveToLocalSession(): void
	{
		if ($application = Application::getInstance())
		{
			$localSession = $application->getLocalSession(static::OPTION_NAME);
			$localSession->set('saved', 'Y');
		}
	}

	protected function getCurrentStep(): ?Step
	{
		return $this->steps->getStepByIndex($this->getCurrentStepIndex());
	}

	protected function isCurrentStepTheLast(): bool
	{
		return ($this->currentStepIndex === $this->steps->getLastStepIndex());
	}

	protected function getCurrentStepIndex(): int
	{
		return $this->currentStepIndex;
	}

	protected function setCurrentStepIndex(int $currentStepIndex): void
	{
		$this->currentStepIndex = $currentStepIndex;
	}

	protected function setNextStepIndex(): void
	{
		$this->currentStepIndex++;
	}

	protected function isFinished(): bool
	{
		return $this->isFinished;
	}

	protected function setIsFinished(bool $isFinished): void
	{
		$this->isFinished = $isFinished;
	}

	protected function getUserId(): int
	{
		return $this->userId;
	}
}
