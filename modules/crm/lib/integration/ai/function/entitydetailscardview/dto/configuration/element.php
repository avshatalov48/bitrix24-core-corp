<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Configuration;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;

final class Element extends Dto
{
	public string $name;
	public bool $isShowAlways = false;
	public array $options = [];

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'options' => new Caster\RawArrayCaster(),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),
		];
	}

	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'optionFlags' => $this->isShowAlways ? "1" : "0",
			'options' => $this->options,
		];
	}
}
