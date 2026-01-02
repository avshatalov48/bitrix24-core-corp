<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Configuration;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\RequiredField;

final class Section extends Dto
{
	public string $name;
	public string $title;
	public readonly string $type;
	public array $elements;

	public function __construct(?array $fields = null)
	{
		parent::__construct($fields);

		$this->type = 'section';
	}

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'elements' => new Caster\CollectionCaster(new Caster\ObjectCaster(Element::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new NotEmptyField($this, 'name'),

			new NotEmptyField($this, 'title'),

			new ObjectCollectionField($this, 'elements'),
			new NotEmptyField($this, 'elements'),
		];
	}
}
