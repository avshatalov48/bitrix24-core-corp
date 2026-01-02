<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Dto\Validator\ScalarCollectionField;
use Bitrix\Crm\Entity\EntityEditorConfig;
use Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto\Configuration\Section;

final class CreateParameters extends Dto
{
	public int $entityTypeId;
	public ?int $categoryId = null;

	public string $title;

	/** @var array<Section> */
	public array $sections;

	public array $userIds = [];
	public bool $common = true;
	public bool $forceSetToUsers = false;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'sections' => new Caster\CollectionCaster(new Caster\ObjectCaster(Section::class)),
			'userIds' => new Caster\CollectionCaster(new Caster\IntCaster()),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),

			new NotEmptyField($this, 'title'),

			new NotEmptyField($this, 'sections'),
			new ObjectCollectionField($this, 'sections'),

			new ScalarCollectionField($this, 'userIds', onlyNotEmptyValues: true),
		];
	}

	public function configuration(): array
	{
		return [
			[
				'name' => 'default_column',
				'type' => 'column',
				'elements' => array_map(static fn (Section $section) => $section->toArray(), $this->sections),
			]
		];
	}

	public function options(): array
	{
		return [
			'forceSetToUsers' => $this->forceSetToUsers,
			'common' => $this->common,
			'categoryName' => EntityEditorConfig::CATEGORY_NAME,
		];
	}
}
