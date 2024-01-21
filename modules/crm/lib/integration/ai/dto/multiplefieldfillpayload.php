<?php

namespace Bitrix\Crm\Integration\AI\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ScalarCollectionField;

class MultipleFieldFillPayload extends Dto
{
	public string $name;

	/** @var string[] */
	public array $aiValues;

	public bool $isApplied = false;
	public bool $isConflict = false;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'aiValues' => new Caster\CollectionCaster(new Caster\StringCaster()),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),

			new ScalarCollectionField($this, 'aiValues', null, true),
			new NotEmptyField($this, 'aiValues'),
		];
	}
}
