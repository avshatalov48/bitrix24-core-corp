<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Tasks\Components\Kanban\ItemField;
use Bitrix\Tasks\Components\Kanban\UserSettings;
use Bitrix\TasksMobile\Dto\TaskFieldDto;
use Bitrix\TasksMobile\Enum\ViewMode;

final class KanbanFieldsProvider
{
	private string $viewMode;
	private bool $isScrum;
	private UserSettings $userSettings;

	public function __construct(string $viewMode, bool $isScrum = false)
	{
		$this->viewMode = ViewMode::validated($viewMode);
		$this->isScrum = $isScrum;
		$this->userSettings = new UserSettings($this->convertViewMode());
	}

	public static function getFullState(bool $isScrum = false): array
	{
		$result = [];

		foreach (ViewMode::values() as $viewMode)
		{
			$provider = new self($viewMode, $isScrum);
			$result[$viewMode] = $provider->getViewState();
		}

		return $result;
	}

	/**
	 * @return ItemField[]
	 */
	public function getPossibleFields(): array
	{
		return [
			$this->userSettings->getProject(),
			$this->userSettings->getAccomplices(),
			$this->userSettings->getAuditors(),
			$this->userSettings->getCheckList(),
			$this->userSettings->getFiles(),
			$this->userSettings->getDateStarted(),
			$this->userSettings->getDateFinished(),
			$this->userSettings->getTimeSpent(),
			$this->userSettings->getId(),
			$this->userSettings->getCrm(),
			$this->userSettings->getTags(),
			$this->userSettings->getMark(),
		];
	}

	public function isFieldVisible(ItemField $field): bool
	{
		$code = $field->getCode();

		if ($this->isScrum && $this->userSettings->isFieldDefault($code))
		{
			return true;
		}
		if (!$this->userSettings->hasSelectedCustomFields() && $this->userSettings->isFieldDefault($code))
		{
			return true;
		}
		if (!$this->isScrum && $this->userSettings->isFieldSelected($code))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return TaskFieldDto[]
	 */
	public function getViewState(): array
	{
		$fields = [];
		foreach ($this->getPossibleFields() as $field)
		{
			$fields[$field->getCode()] = TaskFieldDto::make([
				'code' => $field->getCode(),
				'title' => $field->getTitle(),
				'visible' => $this->isFieldVisible($field),
			]);
		}

		return $fields;
	}

	private function convertViewMode(): string
	{
		if ($this->isScrum)
		{
			return 'kanban_scrum';
		}

		if ($this->viewMode === ViewMode::DEADLINE)
		{
			return 'kanban_timeline_personal';
		}

		if ($this->viewMode === ViewMode::PLANNER)
		{
			return 'kanban_personal';
		}

		return 'kanban';
	}
}
