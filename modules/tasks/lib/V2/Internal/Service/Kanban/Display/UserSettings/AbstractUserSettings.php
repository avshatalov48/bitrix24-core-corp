<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display\UserSettings;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ItemField;
use Bitrix\Tasks\V2\Internal\Service\Kanban\Display\ViewModeType;

abstract class AbstractUserSettings
{
	protected const CUSTOM_SETTINGS_OPTION_CATEGORY = 'tasks';
	protected const CUSTOM_SETTINGS_OPTION_NAME = 'user_selected_fields_for_kanban';

	protected array $userSelectedFields;
	protected ViewModeType $viewMode;

	public function __construct(ViewModeType $viewMode)
	{
		$this->viewMode = $viewMode;
		$this->userSelectedFields = $this->getUserFields();
	}

	public function getPopupSections(int $userId): array
	{
		return array_merge(
			$this->getSectionTitle(),
			$this->getSectionCategories(),
			$this->getSectionOptions($userId),
		);
	}

	public function getId(): ItemField
	{
		return new ItemField(
			'ID',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_ID'),
			'task',
			$this->isFieldSelected('ID'),
			$this->isFieldDefault('ID'),
		);
	}

	public function getTitle(): ItemField
	{
		return new ItemField(
			'TITLE',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_TITLE'),
			'task',
			$this->isFieldSelected('TITLE'),
			$this->isFieldDefault('TITLE'),
		);
	}

	public function getAccomplices(): ItemField
	{
		return new ItemField(
			'ACCOMPLICES',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_ACCOMPLICES'),
			'task',
			$this->isFieldSelected('ACCOMPLICES'),
			$this->isFieldDefault('ACCOMPLICES'),
		);
	}

	public function getAuditors(): ItemField
	{
		return new ItemField(
			'AUDITORS',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_AUDITORS'),
			'task',
			$this->isFieldSelected('AUDITORS'),
			$this->isFieldDefault('AUDITORS'),
		);
	}

	public function getTimeSpent(): ItemField
	{
		return new ItemField(
			'TIME_SPENT',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_TIME_SPENT'),
			'task',
			$this->isFieldSelected('TIME_SPENT'),
			$this->isFieldDefault('TIME_SPENT'),
		);
	}

	public function getCheckList(): ItemField
	{
		return new ItemField(
			'CHECKLIST',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_CHECKLIST'),
			'task',
			$this->isFieldSelected('CHECKLIST'),
			$this->isFieldDefault('CHECKLIST'),
		);
	}

	public function getTags(): ItemField
	{
		return new ItemField(
			'TAGS',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_TAGS'),
			'task',
			$this->isFieldSelected('TAGS'),
			$this->isFieldDefault('TAGS'),
		);
	}

	public function getFiles(): ItemField
	{
		return new ItemField(
			'FILES',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_FILES'),
			'task',
			$this->isFieldSelected('FILES'),
			$this->isFieldDefault('FILES'),
		);
	}

	public function getMark(): ItemField
	{
		return new ItemField(
			'MARK',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_ASSESSMENT'),
			'task',
			$this->isFieldSelected('MARK'),
			$this->isFieldDefault('MARK'),
		);
	}

	public function getCrm(): ItemField
	{
		return new ItemField(
			'CRM',
			Loc::getMessage('TASK_KANBAN_USER_SETTINGS_FIELD_CRM'),
			'task',
			$this->isFieldSelected('CRM'),
			$this->isFieldDefault('CRM'),
		);
	}

	public function isFieldSelected(string $fieldName): bool
	{
		return in_array($fieldName, $this->getSelectedCustomFields());
	}

	public function isFieldDefault(string $fieldName): bool
	{
		return in_array($fieldName, $this->getDefaultFieldCodes());
	}

	public function saveUserSelectedFields(array $selectedFields): bool
	{
		$fieldsToSave = [];
		foreach ($selectedFields as $selectedField)
		{
			if (in_array($selectedField, $this->getAllowedFieldCodes()))
			{
				$fieldsToSave[] = $selectedField;
			}
		}

		if (!empty($fieldsToSave))
		{
			return \CUserOptions::SetOption(
				self::CUSTOM_SETTINGS_OPTION_CATEGORY,
				$this->getOptionKey(),
				$fieldsToSave
			);
		}

		return true;
	}

	/**
	 * @return ItemField[]
	 */
	public function getItemFields(int $userId = 0): array
	{
		return [
			$this->getId(),
			$this->getTitle(),
			$this->getAccomplices(),
			$this->getAuditors(),
			$this->getTimeSpent(),
			$this->getCheckList(),
			$this->getTags(),
			$this->getFiles(),
			$this->getMark(),
			$this->getCrm(),
		];
	}

	public function required(ItemField $itemField): bool
	{
		$fieldCode = $itemField->getCode();

		if (!$this->hasSelectedCustomFields() && $this->isFieldDefault($fieldCode))
		{
			return true;
		}

		if ($this->isFieldSelected($fieldCode))
		{
			return true;
		}

		return false;
	}

	protected function getDefaultFieldCodes(): array
	{
		return [
			'TITLE',
			'DEADLINE',
			'CHECKLIST',
			'TAGS',
			'FILES',
		];
	}

	private function hasSelectedCustomFields(): bool
	{
		return !empty($this->userSelectedFields);
	}

	private function getSectionTitle(): array
	{
		return ['title' => Loc::getMessage('TASK_KANBAN_USER_SETTINGS_POPUP_TITLE')];
	}

	private function getSectionCategories(): array
	{
		return [
			'categories' => [
				[
					'title' => Loc::getMessage('TASK_KANBAN_USER_SETTINGS_SECTION_MAIN'),
					'sectionKey' => 'main_section',
					'key' => 'task',
				],
			],
		];
	}

	private function getUserFields(): array
	{
		return \CUserOptions::GetOption(
			self::CUSTOM_SETTINGS_OPTION_CATEGORY,
			$this->getOptionKey(),
			$this->getDefaultFieldCodes()
		);
	}

	private function getOptionKey(): string
	{
		return self::CUSTOM_SETTINGS_OPTION_NAME . '_' . $this->viewMode->value;
	}

	private function getAllowedFieldCodes(): array
	{
		$allowedFields = [];
		foreach ($this->getItemFields() as $field)
		{
			$allowedFields[] = $field->getCode();
		}

		return $allowedFields;
	}

	private function getSelectedCustomFields(): array
	{
		return $this->userSelectedFields;
	}

	private function getSectionOptions(int $userId): array
	{
		$options = [];
		foreach ($this->getItemFields($userId) as $field)
		{
			$options[] = $field->toArray();
		}

		return ['options' => $options];
	}
}