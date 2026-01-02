<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings;

use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ItemField;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\AbstractUserSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ViewModeType;

class ScrumUserSettings extends AbstractUserSettings
{
	public function __construct()
	{
		parent::__construct(ViewModeType::Scrum);
	}

	public function getEpic(): ItemField
	{
		return new ItemField(
			'EPIC',
			Loc::getMessage('TASK_SCRUM_KANBAN_USER_SETTINGS_FIELD_EPIC'),
			'task',
			$this->isFieldSelected('EPIC'),
			$this->isFieldDefault('EPIC'),
		);
	}

	public function getStoryPoints(): ItemField
	{
		return new ItemField(
			'STORY_POINTS',
			Loc::getMessage('TASK_SCRUM_KANBAN_USER_SETTINGS_FIELD_STORY_POINTS'),
			'task',
			$this->isFieldSelected('STORY_POINTS'),
			$this->isFieldDefault('STORY_POINTS'),
		);
	}

	/**
	 * @return ItemField[]
	 */
	public function getItemFields(int $userId = 0): array
	{
		$itemFields = [
			$this->getEpic(),
			$this->getStoryPoints(),
		];

		return array_merge(parent::getItemFields(), $itemFields);
	}

	protected function getDefaultFieldCodes(): array
	{
		return array_merge(parent::getDefaultFieldCodes(), ['EPIC', 'STORY_POINTS']);
	}
}