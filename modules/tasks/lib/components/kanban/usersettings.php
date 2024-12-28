<?php

namespace Bitrix\Tasks\Components\Kanban;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Integration\Extranet\User;

class UserSettings
{
	// Keys used for user settings table
	public const CUSTOM_SETTINGS_OPTION_CATEGORY = 'tasks';
	public const CUSTOM_SETTINGS_OPTION_NAME = 'user_selected_fields_for_kanban';
	private array $userSelectedFields;
	private array $allowedElements = [];
	private string $viewMode = '';

	public function __construct(string $viewMode)
	{
		$this->viewMode = $viewMode;
		$this->userSelectedFields = $this->getUserFields();
		$this->specifyAllowedElements();
	}

	public function hasSelectedCustomFields(): bool
	{
		return count($this->userSelectedFields) > 0;
	}

	public function getSelectedCustomFields(): array
	{
		return $this->userSelectedFields;
	}

	public function getDefaultFieldCodes(): array
	{
		$defaultFieldCodes = [
			'TITLE',
			'DEADLINE',
			'CHECKLIST',
			'TAGS',
			'FILES',
		];

		if (in_array($this->viewMode, ['kanban_scrum', 'kanban'], true) && FlowFeature::isOn())
		{
			$defaultFieldCodes[] = 'FLOW';
		}

		return $defaultFieldCodes;
	}

	public function getPopupSections(): array
	{
		$popupSections = [
			'title' => Loc::getMessage('TASK_KANBAN_USER_SETTINGS_POPUP_TITLE'),
			// 'sections' => [
			// 	[
			// 		'key' => 'main_section',
			// 		'title' => Loc::getMessage('TASK_KANBAN_USER_SETTINGS_SECTION_MAIN'),
			// 		'value' => true,
			// 	],
			// ],
			'categories' => [
				[
					'title' => Loc::getMessage('TASK_KANBAN_USER_SETTINGS_SECTION_MAIN'),
					'sectionKey' => 'main_section',
					'key' => 'task',
				],
			],
			'options' => [
				$this->getId()->toArray(),
				$this->getTitle()->toArray(),
				$this->getDeadLine()->toArray(),
				$this->getDateStarted()->toArray(),
				$this->getAccomplices()->toArray(),
				$this->getAuditors()->toArray(),
				$this->getTimeSpent()->toArray(),
				$this->getCheckList()->toArray(),
				$this->getTags()->toArray(),
				$this->getFiles()->toArray(),
				$this->getProject()->toArray(),
			],
		];

		if (FlowFeature::isOn())
		{
			$popupSections['options'][] = $this->getFlow()->toArray();
		}

		$popupSections['options'] = array_merge(
			$popupSections['options'],
			[
				$this->getDateFinished()->toArray(),
				$this->getMark()->toArray(),
				$this->getCrm()->toArray(),
			],
		);

		return $popupSections;
	}

	public function saveUserSelectedFields(array $selectedFields): bool
	{
		// filter by allowed field names
		$fieldsToSave = [];
		foreach ($selectedFields as $selectedField)
		{
			if (in_array($selectedField, $this->allowedElements))
			{
				$fieldsToSave[] = $selectedField;
			}
		}

		// save as user selected fields
		if (count($fieldsToSave) > 0)
		{
			return \CUserOptions::SetOption(
				self::CUSTOM_SETTINGS_OPTION_CATEGORY,
				$this->getOptionKey(),
				$fieldsToSave
			);
		}
		return true;
	}

	public function isFieldSelected(string $fieldName): bool
	{
		return in_array($fieldName, $this->getSelectedCustomFields());
	}

	public function isFieldDefault(string $fieldName): bool
	{
		return in_array($fieldName, $this->getDefaultFieldCodes());
	}

	private function specifyAllowedElements(): void
	{
		$options = isset($this->getPopupSections()['options'])
			? $this->getPopupSections()['options']
			: [];

		foreach ($options as $element)
		{
			$this->allowedElements[] = $element['id'];
		}
	}

	private function getUserFields(): array
	{
		return \CUserOptions::GetOption(
			self::CUSTOM_SETTINGS_OPTION_CATEGORY,
			$this->getOptionKey(),
			$this->getDefaultFieldCodes()
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

	public function getProject(): ItemField
	{
		$isCollaber = User::isCollaber((int) CurrentUser::get()->getId());
		$itemName = $isCollaber ? 'TASK_KANBAN_USER_SETTINGS_FIELD_COLLAB' : 'TASK_KANBAN_USER_SETTINGS_FIELD_PROJECT';

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

	private function getOptionKey(): string
	{
		return self::CUSTOM_SETTINGS_OPTION_NAME . '_' . $this->viewMode;
	}
}
