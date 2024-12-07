<?php

namespace Bitrix\Crm\Restriction;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EventManager;
use Bitrix\Main\Result;

final class AutomatedSolutionLimit
{
	private ?int $count = null;
	private array $eventHandlerIds = [];

	public function __construct()
	{
		$eventManager = EventManager::getInstance();

		$this->eventHandlerIds[AutomatedSolutionTable::EVENT_ON_AFTER_ADD] = $eventManager->addEventHandler(
			AutomatedSolutionTable::class,
			AutomatedSolutionTable::EVENT_ON_AFTER_ADD,
			function (): void {
				if ($this->count !== null)
				{
					$this->count++;
				}
			}
		);

		$this->eventHandlerIds[AutomatedSolutionTable::EVENT_ON_AFTER_DELETE] = $eventManager->addEventHandler(
			AutomatedSolutionTable::class,
			AutomatedSolutionTable::EVENT_ON_AFTER_DELETE,
			function (): void {
				if ($this->count !== null)
				{
					$this->count--;
				}
			},
		);
	}

	public function __destruct()
	{
		$eventManager = EventManager::getInstance();

		foreach ($this->eventHandlerIds as $eventName => $handlerId)
		{
			$eventManager->removeEventHandler(
				AutomatedSolutionTable::class,
				$eventName,
				$handlerId,
			);
		}
	}

	public function check(): Result
	{
		$result = new Result();

		if ($this->isExceeded())
		{
			$result->addError($this->getLimitExceededError());
		}

		return $result;
	}

	private function isExceeded(): bool
	{
		return $this->getCount() >= $this->getLimit();
	}

	private function getLimitExceededError(): Error
	{
		return new Error(
			Loc::getMessage('CRM_RESTRICTION_AUTOMATED_SOLUTION_LIMIT_EXCEEDED'),
			'LIMIT_EXCEEDED',
			[
				'sliderCode' => $this->getSliderCode(),
			],
		);
	}

	private function getCount(): int
	{
		if ($this->count === null)
		{
			$this->count = AutomatedSolutionTable::getCount();
		}

		return $this->count;
	}

	private function getSliderCode(): ?string
	{
		if (!$this->isBitrix24())
		{
			return null;
		}

		if (Bitrix24Manager::isEnterprise())
		{
			return 'limit_automated_solution_max_number';
		}

		if ($this->isFeatureInTariff())
		{
			return 'limit_20_automated_solution';
		}

		return 'limit_automated_solution';
	}

	private function isFeatureInTariff(): bool
	{
		return $this->getLimit() > 0;
	}

	private function getLimit(): int
	{
		if ($this->isBitrix24())
		{
			return (int)Bitrix24Manager::getVariable('crm_automated_solution_limit');
		}

		return (int)Option::get('crm', 'automated_solution_limit', 200);
	}

	private function isBitrix24(): bool
	{
		return Bitrix24Manager::isEnabled();
	}
}
