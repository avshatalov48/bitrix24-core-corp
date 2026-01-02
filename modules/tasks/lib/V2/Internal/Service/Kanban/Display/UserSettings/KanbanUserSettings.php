<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Integration\Extranet\User;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ItemField;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings\AbstractUserSettings;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ViewModeType;

class KanbanUserSettings extends AbstractUserSettings
{
	public function __construct(
		ViewModeType $viewModeType = ViewModeType::Kanban,
	)
	{
		parent::__construct($viewModeType);
	}

	public function getDeadLine(): ItemField
	{
		return new ItemField(
			'DEADLINE',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_DEADLINE'),
			'task',
			$this->isFieldSelected('DEADLINE'),
			$this->isFieldDefault('DEADLINE'),
		);
	}

	public function getDateStarted(): ItemField
	{
		return new ItemField(
			'DATE_STARTED',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_DATE_STARTED_MSGVER_1'),
			'task',
			$this->isFieldSelected('DATE_STARTED'),
			$this->isFieldDefault('DATE_STARTED'),
		);
	}

	public function getProject(int $userId = 0): ItemField
	{
		$itemName =
			($userId > 0 && User::isCollaber($userId))
				? 'TASK_KANBAN_USER_SETTINGS_FIELD_COLLAB'
				: 'TASK_KANBAN_USER_SETTINGS_FIELD_PROJECT'
		;

		return new ItemField(
			'PROJECT',
			Loc::getMessage($itemName),
			'task',
			$this->isFieldSelected('PROJECT'),
			$this->isFieldDefault('PROJECT'),
		);
	}

	public function getFlow(): ItemField
	{
		return new ItemField(
			'FLOW',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_FLOW'),
			'task',
			$this->isFieldSelected('FLOW'),
			$this->isFieldDefault('FLOW'),
		);
	}

	public function getDateFinished(): ItemField
	{
		return new ItemField(
			'DATE_FINISHED',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_DATE_FINISHED'),
			'task',
			$this->isFieldSelected('DATE_FINISHED'),
			$this->isFieldDefault('DATE_FINISHED'),
		);
	}

	/**
	 * @return ItemField[]
	 */
	public function getItemFields(int $userId = 0): array
	{
		$itemFields = [
			$this->getDeadLine(),
			$this->getDateStarted(),
			$this->getProject($userId),
			$this->getDateFinished(),
		];

		if (FlowFeature::isOn())
		{
			$itemFields[] = $this->getFlow();
		}

		return array_merge(parent::getItemFields(), $itemFields);
	}

	protected function getDefaultFieldCodes(): array
	{
		$defaultFieldCodes = parent::getDefaultFieldCodes();

		if (FlowFeature::isOn())
		{
			$defaultFieldCodes[] = 'FLOW';
		}

		return $defaultFieldCodes;
	}
}
