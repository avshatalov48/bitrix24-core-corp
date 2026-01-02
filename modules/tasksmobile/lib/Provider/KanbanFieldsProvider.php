<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\DisplayFactory;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\AbstractUserSettings;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ViewModeType;
use Bitrix\TasksMobile\Dto\TaskFieldDto;
use Bitrix\TasksMobile\Enum\ViewMode;

final class KanbanFieldsProvider
{
	private string $viewMode;
	private bool $isScrum;
	private AbstractUserSettings $userSettings;

	public static function getFullState(int $userId, bool $isScrum = false): array
	{
		$result = [];

		foreach (ViewMode::values() as $viewMode)
		{
			$provider = new self($viewMode, $isScrum);
			$result[$viewMode] = $provider->getViewState($userId);
		}

		return $result;
	}

	private function __construct(string $viewMode, bool $isScrum = false)
	{
		$this->viewMode = ViewMode::validated($viewMode);
		$this->isScrum = $isScrum;

		$this->userSettings = DisplayFactory::getInstance()->createUserSettings($this->convertViewMode());
	}

	/**
	 * @return TaskFieldDto[]
	 */
	private function getViewState(int $userId): array
	{
		$fields = [];
		foreach ($this->userSettings->getItemFields($userId) as $field)
		{
			$fields[$field->getCode()] = TaskFieldDto::make([
				'code' => $field->getCode(),
				'title' => $field->getTitle(),
				'visible' => $this->userSettings->required($field),
			]);
		}

		return $fields;
	}

	private function convertViewMode(): ViewModeType
	{
		if ($this->isScrum)
		{
			return ViewModeType::Scrum;
		}

		if ($this->viewMode === ViewMode::DEADLINE)
		{
			return ViewModeType::KanbanTimelinePersonal;
		}

		if ($this->viewMode === ViewMode::PLANNER)
		{
			return ViewModeType::KanbanPersonal;
		}

		return ViewModeType::Kanban;
	}
}
