<?php
namespace Bitrix\Tasks\Scrum\Internal;

class EntityInfoColumn
{
	private $sprintGoal = '';

	public function getInfoData(): array
	{
		return [
			$this->getSprintGoalKey() => $this->getSprintGoal()
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (isset($infoData[$this->getSprintGoalKey()]) && is_string($infoData[$this->getSprintGoalKey()]))
		{
			$this->setSprintGoal($infoData[$this->getSprintGoalKey()]);
		}
	}

	/**
	 * @return string
	 */
	public function getSprintGoalKey(): string
	{
		return 'sprintGoal';
	}

	/**
	 * @return string
	 */
	public function getSprintGoal(): string
	{
		return $this->sprintGoal;
	}

	/**
	 * @param string $sprintGoal
	 */
	public function setSprintGoal(string $sprintGoal): void
	{
		$this->sprintGoal = $sprintGoal;
	}
}