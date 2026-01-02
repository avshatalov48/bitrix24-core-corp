<?php

namespace Bitrix\Crm\Integration\AI\Function\UserField\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Integration\AI\Function\UserField\Enum\UserFieldType;

class UniversalCreateUserFieldParameters extends Dto
{
	public string $type;
	public bool $isMultiple;

	public ?int $entityTypeId = null;
	public ?int $categoryId = null;
	public ?string $label = null;
	public ?array $enumerationList = null;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'enumerationList' => new Caster\RawArrayCaster(),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'type'),
			new EnumField($this, 'type', UserFieldType::values()),

			new RequiredField($this, 'isMultiple'),
		];
	}

	public function toCreateUserFieldParameters(): array
	{
		$data = [
			'entityTypeId' => $this->entityTypeId,
			'categoryId' => $this->categoryId,
			'label' => $this->label,
		];

		if ($this->type === UserFieldType::Enumeration->id())
		{
			$data['enumerationList'] = $this->enumerationList;
		}

		return $data;
	}
}
