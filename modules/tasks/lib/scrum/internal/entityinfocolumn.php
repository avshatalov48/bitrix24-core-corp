<?php
namespace Bitrix\Tasks\Scrum\Internal;

class EntityInfoColumn
{
	private $sprintGoal = '';
	private $typesGenerated = 'N';

	public function getInfoData(): array
	{
		return [
			$this->getSprintGoalKey() => $this->getSprintGoal(),
			$this->getTypesGeneratedKey() => $this->getTypesGenerated(),
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (isset($infoData[$this->getSprintGoalKey()]) && is_string($infoData[$this->getSprintGoalKey()]))
		{
			$this->setSprintGoal($infoData[$this->getSprintGoalKey()]);
		}

		if (isset($infoData[$this->getTypesGeneratedKey()]) && is_string($infoData[$this->getTypesGeneratedKey()]))
		{
			$this->setTypesGenerated($infoData[$this->getTypesGeneratedKey()]);
		}
	}

	public function getSprintGoalKey(): string
	{
		return 'sprintGoal';
	}

	public function getTypesGeneratedKey(): string
	{
		return 'typesGenerated';
	}

	public function getSprintGoal(): string
	{
		return $this->sprintGoal;
	}

	public function getTypesGenerated(): string
	{
		return $this->typesGenerated;
	}

	public function isTypesGenerated(): bool
	{
		return $this->typesGenerated === 'Y';
	}

	public function setSprintGoal(string $sprintGoal): void
	{
		$this->sprintGoal = $sprintGoal;
	}

	public function setTypesGenerated(string $typesGenerated): void
	{
		$availableValues = ['Y', 'N'];
		if (!in_array($typesGenerated, $availableValues))
		{
			$typesGenerated = 'N';
		}
		$this->typesGenerated = $typesGenerated;
	}
}