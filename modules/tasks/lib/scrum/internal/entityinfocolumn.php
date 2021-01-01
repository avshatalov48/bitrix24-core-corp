<?php
namespace Bitrix\Tasks\Scrum\Internal;

class EntityInfoColumn
{
	private $sprintGoal = '';
	private $dodItemsRequired = 'Y';

	public function getInfoData(): array
	{
		return [
			$this->getSprintGoalKey() => $this->getSprintGoal(),
			$this->getDodItemsRequiredKey() => $this->getDodItemsRequired()
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (isset($infoData[$this->getSprintGoalKey()]) && is_string($infoData[$this->getSprintGoalKey()]))
		{
			$this->setSprintGoal($infoData[$this->getSprintGoalKey()]);
		}

		if (isset($infoData[$this->getDodItemsRequiredKey()]) && is_string($infoData[$this->getDodItemsRequiredKey()]))
		{
			$this->setDodItemsRequired($infoData[$this->getDodItemsRequiredKey()]);
		}
	}

	public function getSprintGoalKey(): string
	{
		return 'sprintGoal';
	}

	public function getSprintGoal(): string
	{
		return $this->sprintGoal;
	}

	public function setSprintGoal(string $sprintGoal): void
	{
		$this->sprintGoal = $sprintGoal;
	}

	public function getDodItemsRequiredKey(): string
	{
		return 'dodItemsRequired';
	}

	public function getDodItemsRequired(): string
	{
		return $this->dodItemsRequired;
	}

	public function setDodItemsRequired(string $dodItemsRequired): void
	{
		$availableValues = ['Y', 'N'];
		if (!in_array($dodItemsRequired, $availableValues))
		{
			$dodItemsRequired = 'Y';
		}
		$this->dodItemsRequired = $dodItemsRequired;
	}
}