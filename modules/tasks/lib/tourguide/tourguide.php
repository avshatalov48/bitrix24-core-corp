<?php
namespace Bitrix\Tasks\TourGuide;

use Bitrix\Main\Application;
use Bitrix\Tasks\TourGuide\Internals\Step;
use Bitrix\Tasks\TourGuide\Internals\StepQueue;
use Bitrix\Tasks\TourGuide\Internals\Storage;
use CUserOptions;

abstract class TourGuide
{
	protected const OPTION_CATEGORY = 'tasks.tour.guides';
	protected const OPTION_NAME = '';
	protected const OPTION_YES = 'Y';
	protected const OPTION_NO = 'N';

	protected static array $instances = [];

	private StepQueue $steps;
	protected ?Storage $storage = null;

	protected int $userId;
	protected mixed $data= [];
	private int $currentStepIndex = 0;
	private bool $isFinished = false;

	abstract public function proceed(): bool;
	abstract protected function getDefaultSteps(): array;
	abstract protected function loadPopupData(): array;

	private function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->init();
	}

	public static function getInstance(int $userId): self
	{
		if (!isset(static::$instances[static::class]))
		{
			static::$instances[static::class] = new static($userId);
		}

		return static::$instances[static::class];
	}

	public function getCurrentStepPopupData(): array
	{
		if ($currentStep = $this->getCurrentStep())
		{
			return $currentStep->getPopupData();
		}

		return [];
	}

	public function finish(): void
	{
		$this->setIsFinished(true);
		$this->getCurrentStep() && $this->getCurrentStep()->finish();
		$this->saveData();
	}

	public function getStepsPopupData(): array
	{
		return $this->steps->getPopupData();
	}

	public function isFirstExperience(): bool
	{
		return false;
	}

	protected function preload(): static
	{
		if (is_null($this->storage))
		{
			$this->storage = new Storage(...$this->getTourGuides());
		}

		return $this;
	}

	protected function hasActiveGuides(): bool
	{
		$this->preload();
		return $this->storage->hasActiveGuides($this);
	}

	protected function hasActiveFirstExperienceGuides(): bool
	{
		$this->preload();
		return $this->storage->hasActiveFirstExperienceGuides($this);
	}

	protected function loadData($defaultValue = []): void
	{
		$this->data = CUserOptions::GetOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME,
			$defaultValue,
			$this->getUserId()
		);

		$this->convertFromOption();

		if (array_key_exists('currentStepIndex', $this->data))
		{
			$this->currentStepIndex = $this->data['currentStepIndex'];
		}
		if (array_key_exists('isFinished', $this->data))
		{
			$this->isFinished = $this->data['isFinished'];
		}

		$this->setSteps(($this->data['steps'] ?? []));
		$this->setStepsPopupData();
	}

	protected function setStepsPopupData(): void
	{
		$this->steps->setPopupData($this->loadPopupData());
	}

	protected function saveData(): void
	{
		CUserOptions::SetOption(
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

	private function init(): void
	{
		$this->steps = new StepQueue();
		$this->loadData();
	}

	private function getTourGuides(): array
	{
		return [
			FirstGridTaskCreation::getInstance($this->userId),
			FirstTimelineTaskCreation::getInstance($this->userId),
			FirstProjectCreation::getInstance($this->userId),
			FirstScrumCreation::getInstance($this->userId),
			ExpiredTasksDeadlineChange::getInstance($this->userId),
			PresetsMoved::getInstance($this->userId),
		];
	}

	private function setSteps(array $steps): void
	{
		$this->steps->setSteps((empty($steps) ? $this->getDefaultSteps() : $steps));
	}

	private function convertFromOption(): void
	{
		if ($this->isOption())
		{
			$this->data = [
				'currentStepIndex' => 0,
				'isFinished' => $this->data === static::OPTION_YES,
				'steps' => [],
			];
		}
	}

	private function isOption(): bool
	{
		if (!is_string($this->data))
		{
			return false;
		}

		return $this->data === static::OPTION_YES || $this->data === static::OPTION_NO;
	}
}
