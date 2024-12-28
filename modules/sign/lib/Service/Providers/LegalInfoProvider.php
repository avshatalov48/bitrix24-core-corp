<?php

namespace Bitrix\Sign\Service\Providers;

use Bitrix\Sign\Config\LegalInfo;
use Bitrix\Sign\Item\B2e\LegalInfoField;
use Bitrix\Sign\Type\FieldType;

class LegalInfoProvider extends InfoProvider
{
	private array $legalFieldsByType;

	public const USER_FIELD_ENTITY_ID = LegalInfo::USER_FIELD_ENTITY_ID;
	public const LEGAL_USER_FIELD_DEFAULT = [
		'UF_LEGAL_ADDRESS',
		'UF_LEGAL_INN',
		'UF_LEGAL_SNILS',
		'UF_LEGAL_POSITION',
		'UF_LEGAL_PATRONYMIC_NAME',
		'UF_LEGAL_LAST_NAME',
		'UF_LEGAL_NAME',
	];

	/**
	 * @return array<LegalInfoField>
	 */
	public function getFieldsItems(): array
	{
		return array_map(static fn(array $field): LegalInfoField => new LegalInfoField(
			type: $field['type'],
			caption: $field['caption'],
			name: $field['sourceName'],
		), $this->getFieldsMap());
	}

	protected function getType(array $field): string
	{
		return match ($field['FIELD_NAME'] ?? '')
		{
			'UF_LEGAL_NAME' => FieldType::FIRST_NAME,
			'UF_LEGAL_LAST_NAME' => FieldType::LAST_NAME,
			'UF_LEGAL_PATRONYMIC_NAME' => FieldType::PATRONYMIC,
			'UF_LEGAL_POSITION' => FieldType::POSITION,
			default => parent::getType($field),
		};
	}

	public function getFirstFieldNameByType(string $fieldType): ?string
	{
		return $this->getLegalInfoFieldByType($fieldType)?->name;
	}

	public function getLegalInfoFieldByType(string $type): ?LegalInfoField
	{
		if (!isset($this->legalFieldsByType))
		{
			$this->legalFieldsByType = [];
			foreach ($this->getFieldsItems() as $field)
			{
				$this->legalFieldsByType[$field->type] = $field;
			}
		}

		return $this->legalFieldsByType[$type] ?? null;
	}
}
