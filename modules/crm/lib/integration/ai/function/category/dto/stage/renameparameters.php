<?php

namespace Bitrix\Crm\Integration\AI\Function\Category\Dto\Stage;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\EntityType\IsPossibleDynamicType;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\Logic;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use CCrmOwnerType;

final class RenameParameters extends Dto
{
	public int $entityTypeId;
	public ?int $categoryId;

	/** @var RenameItem[] */
	public array $names;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'names' => new Caster\CollectionCaster(new Caster\ObjectCaster(RenameItem::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			Logic::or($this, [
				new EnumField($this, 'entityTypeId', [
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Quote,
					CCrmOwnerType::SmartInvoice,
				]),
				new IsPossibleDynamicType($this, 'entityTypeId'),
			]),

			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),

			new ObjectCollectionField($this, 'names'),
			new NotEmptyField($this, 'names'),
		];
	}

	public function getRenameValue(string $stageId): ?string
	{
		foreach ($this->names as $item)
		{
			if ($item->stageId === $stageId)
			{
				return $item->name;
			}
		}

		return null;
	}
}
