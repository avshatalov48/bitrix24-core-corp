<?php

namespace Bitrix\Tasks\Scrum\Form;

class EntityInfo
{
	private $sprintGoal = '';
	private $typesGenerated = 'N';
	private $typesParticipantsGenerated = 'N';
	private $events = [];
	private $templatesClosed = 'N';
	private $sprintStagesRecoveryStatus = 'checked';
	private $hiddenVisibilityList = [];

	public function getInfoData(): array
	{
		return [
			$this->getSprintGoalKey() => $this->getSprintGoal(),
			$this->getTypesGeneratedKey() => $this->getTypesGenerated(),
			$this->getTypesParticipantsGeneratedKey() => $this->getTypesParticipantsGenerated(),
			$this->getEventsKey() => $this->getEvents(),
			$this->getTemplatesClosedKey() => $this->getTemplatesClosed(),
			$this->getSprintStagesRecoveryStatusKey() => $this->getSprintStagesRecoveryStatus(),
			$this->getHiddenVisibilityListKey() => $this->getHiddenVisibilityList(),
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (
			isset($infoData[$this->getSprintGoalKey()])
			&& is_string($infoData[$this->getSprintGoalKey()])
		)
		{
			$this->setSprintGoal($infoData[$this->getSprintGoalKey()]);
		}

		if (
			isset($infoData[$this->getTypesGeneratedKey()])
			&& is_string($infoData[$this->getTypesGeneratedKey()])
		)
		{
			$this->setTypesGenerated($infoData[$this->getTypesGeneratedKey()]);
		}

		if (
			isset($infoData[$this->getTypesParticipantsGeneratedKey()])
			&& is_string($infoData[$this->getTypesParticipantsGeneratedKey()])
		)
		{
			$this->setTypesParticipantsGenerated($infoData[$this->getTypesParticipantsGeneratedKey()]);
		}

		if (isset($infoData[$this->getEventsKey()]) && is_array($infoData[$this->getEventsKey()]))
		{
			$this->setEvents($infoData[$this->getEventsKey()]);
		}

		if (
			isset($infoData[$this->getTemplatesClosedKey()])
			&& is_string($infoData[$this->getTemplatesClosedKey()])
		)
		{
			$this->setTemplatesClosed($infoData[$this->getTemplatesClosedKey()]);
		}

		if (
			isset($infoData[$this->getSprintStagesRecoveryStatusKey()])
			&& is_string($infoData[$this->getSprintStagesRecoveryStatusKey()])
		)
		{
			$this->setSprintStagesRecoveryStatus($infoData[$this->getSprintStagesRecoveryStatusKey()]);
		}

		if (
			isset($infoData[$this->getHiddenVisibilityListKey()])
			&& is_array($infoData[$this->getHiddenVisibilityListKey()])
		)
		{
			$this->setHiddenVisibilityList($infoData[$this->getHiddenVisibilityListKey()]);
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

	public function getTypesParticipantsGeneratedKey(): string
	{
		return 'typesParticipantsGenerated';
	}

	public function getEventsKey(): string
	{
		return 'events';
	}

	public function getTemplatesClosedKey(): string
	{
		return 'templatesClosed';
	}

	public function getSprintStagesRecoveryStatusKey(): string
	{
		return 'sprintStagesRecoveryStatus';
	}

	public function getHiddenVisibilityListKey(): string
	{
		return 'visibilityList';
	}

	public function getSprintGoal(): string
	{
		return $this->sprintGoal;
	}

	public function getTypesGenerated(): string
	{
		return $this->typesGenerated;
	}

	public function getTypesParticipantsGenerated(): string
	{
		return $this->typesParticipantsGenerated;
	}

	public function getEvents(): array
	{
		return $this->events;
	}

	public function getTemplatesClosed(): string
	{
		return $this->templatesClosed;
	}

	public function getSprintStagesRecoveryStatus(): string
	{
		return $this->sprintStagesRecoveryStatus;
	}

	public function getHiddenVisibilityList(): array
	{
		return $this->hiddenVisibilityList;
	}

	public function isTypesGenerated(): bool
	{
		return $this->typesGenerated === 'Y';
	}

	public function isTypesParticipantsGenerated(): bool
	{
		return $this->typesParticipantsGenerated === 'Y';
	}

	public function isTemplatesClosed(): bool
	{
		return $this->templatesClosed === 'Y';
	}

	public function isVisibility(int $userId): bool
	{
		return !in_array($userId, $this->hiddenVisibilityList, true);
	}

	public function sprintStagesRecoveryStatusIsVerified(): bool
	{
		return (
			$this->sprintStagesRecoveryStatus === 'checked'
			|| $this->sprintStagesRecoveryStatus === 'completed'
		);
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

	public function setTypesParticipantsGenerated(string $typesParticipantsGenerated): void
	{
		$availableValues = ['Y', 'N'];
		if (!in_array($typesParticipantsGenerated, $availableValues))
		{
			$typesParticipantsGenerated = 'N';
		}

		$this->typesParticipantsGenerated = $typesParticipantsGenerated;
	}

	/**
	 * Save the identifier of the event that is generated from the template.
	 *
	 * @param array $map The relationship between the template ID and the generated event.
	 */
	public function setEvents(array $map): void
	{
		$availableTemplateIds = ['daily', 'planning', 'review', 'retrospective'];

		foreach ($map as $templateId => $eventId)
		{
			$templateId = (is_string($templateId) ? $templateId : '');
			$eventId = (is_numeric($eventId) ? (int) $eventId : 0);
			if (in_array($templateId, $availableTemplateIds, true))
			{
				$this->events[$templateId] = $eventId;
			}
		}
	}

	public function setTemplatesClosed(string $templatesClosed): void
	{
		$availableValues = ['Y', 'N'];
		if (!in_array($templatesClosed, $availableValues))
		{
			$templatesClosed = 'N';
		}
		$this->templatesClosed = $templatesClosed;
	}

	public function setSprintStagesRecoveryStatusToWaiting(): void
	{
		$this->sprintStagesRecoveryStatus = 'waiting';
	}

	public function setSprintStagesRecoveryStatusToChecked(): void
	{
		$this->sprintStagesRecoveryStatus = 'checked';
	}

	public function setSprintStagesRecoveryStatusToCompleted(): void
	{
		$this->sprintStagesRecoveryStatus = 'completed';
	}

	public function setSprintStagesRecoveryStatus(string $status): void
	{
		$availableValues = ['waiting', 'checked', 'completed'];

		if (!in_array($status, $availableValues))
		{
			$status = 'checked';
		}

		$this->sprintStagesRecoveryStatus = $status;
	}

	public function setHiddenVisibilityList(array $list): void
	{
		if ($list === array_filter($list, 'is_int'))
		{
			$this->hiddenVisibilityList = $list;
		}
	}

	public function showVisibility(int $userId): void
	{
		$userId = (int) $userId;

		if (in_array($userId, $this->hiddenVisibilityList, true))
		{
			$this->hiddenVisibilityList = array_values(
				array_diff($this->hiddenVisibilityList, [$userId])
			);
		}
	}

	public function hideVisibility(int $userId): void
	{
		$userId = (int) $userId;

		if (!in_array($userId, $this->hiddenVisibilityList, true))
		{
			$this->hiddenVisibilityList[] = $userId;
		}
	}
}