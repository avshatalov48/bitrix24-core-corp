<?php

namespace Bitrix\TasksMobile\UserField\Field;

use Bitrix\Main\Localization\LanguageTable;
use Bitrix\TasksMobile\UserField\Type;

abstract class BaseField
{
	protected readonly int $id;
	protected readonly Type $type;
	protected readonly string $entityId;
	protected readonly string $fieldName;
	protected readonly string $title;
	protected readonly string|array $value;
	protected readonly int $sort;
	protected readonly bool $isMandatory;
	protected readonly bool $isMultiple;
	protected readonly bool $isVisible;
	protected readonly bool $isEditable;
	protected readonly array $settings;

	public function __construct(array $field)
	{
		$this->id = (int)$field['ID'];
		$this->type = $field['TYPE'];
		$this->entityId = $field['ENTITY_ID'];
		$this->sort = (int)$field['SORT'];
		$this->fieldName = $field['FIELD_NAME'];
		$this->isMandatory = $field['MANDATORY'] === 'Y';
		$this->isMultiple = $field['MULTIPLE'] === 'Y';
		$this->isVisible = $field['SHOW_IN_LIST'] === 'Y';
		$this->isEditable = $field['EDIT_IN_LIST'] === 'Y';

		$this->settings = $this->prepareSettings($field['SETTINGS']);
		$this->title = $this->prepareTitle($field['EDIT_FORM_LABEL']);
		$this->value = $this->prepareValue($field['VALUE']);
	}

	private function prepareTitle(?string $editFormLabel = ''): string
	{
		$ufLabel = ($editFormLabel ?? '');
		if ($ufLabel === '')
		{
			$userField = \CUserTypeEntity::GetByID($this->id);
			$userFieldLabels = ($userField['EDIT_FORM_LABEL'] ?? null);

			if (!empty($userFieldLabels))
			{
				foreach ($this->getLanguages() as $languageId)
				{
					if (isset($userFieldLabels[$languageId]))
					{
						$ufLabel = $userFieldLabels[$languageId];
						break;
					}
				}

				if ($ufLabel === '')
				{
					reset($userFieldLabels);
					$ufLabel = current($userFieldLabels);
				}
			}
		}

		return ($ufLabel ?? $this->fieldName);
	}

	private function getLanguages(): array
	{
		$languages = [];

		try
		{
			$languageList = LanguageTable::getList([
				'order' => 'SORT',
				'cache' => [
					'ttl' => 86400,
				],
			]);
			while ($language = $languageList->fetch())
			{
				$languages[] = $language['LID'];
			}
		}
		catch (\Exception $ex)
		{
			return $languages;
		}

		return $languages;
	}

	private function prepareValue(array|string|null $value = ''): array|string
	{
		if ($this->isMultiple)
		{
			if (!is_array($value))
			{
				$value = [$value];
			}

			if (empty($value))
			{
				$value = [''];
			}

			$preparedValue = [];
			foreach ($value as $singleValue)
			{
				$preparedValue[] = $this->prepareSingleValue($singleValue);
			}

			return $preparedValue;
		}

		return $this->prepareSingleValue($value);
	}

	protected function prepareSingleValue(string $value = ''): string
	{
		return $value;
	}

	protected function prepareSettings(array $settings): array
	{
		return $settings;
	}

	public function toDto(): array
	{
		return [
			'ID' => $this->id,
			'TYPE' => $this->type->value,
			'ENTITY_ID' => $this->entityId,
			'FIELD_NAME' => $this->fieldName,
			'TITLE' => $this->title,
			'VALUE' => $this->value,
			'SORT' => $this->sort,
			'IS_MANDATORY' => $this->isMandatory,
			'IS_MULTIPLE' => $this->isMultiple,
			'IS_VISIBLE' => $this->isVisible,
			'IS_EDITABLE' => $this->isEditable,
			'SETTINGS' => $this->settings,
		];
	}

	public function getId(): int
	{
		return $this->id;
	}
}
