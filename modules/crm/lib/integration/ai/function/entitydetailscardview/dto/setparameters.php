<?php

namespace Bitrix\Crm\Integration\AI\Function\EntityDetailsCardView\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\DefinedCategoryIdentifier;
use Bitrix\Crm\Dto\Validator\EnumField;
use Bitrix\Crm\Dto\Validator\IntegerField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Dto\Validator\ScalarCollectionField;
use Bitrix\Crm\Entity\EntityEditorConfigScope;

final class SetParameters extends Dto
{
	public int $entityTypeId;
	public ?int $categoryId = null;

	public string $scope;
	public ?int $customScopeId = null;
	public array $userIds = [];

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match($propertyName) {
			'userIds' => new Caster\CollectionCaster(new Caster\IntCaster()),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		$validators = [
			new DefinedCategoryIdentifier($this, 'entityTypeId', 'categoryId'),

			new RequiredField($this, 'scope'),
			new EnumField($this, 'scope', [
				EntityEditorConfigScope::COMMON,
				EntityEditorConfigScope::CUSTOM,
			]),

			new ScalarCollectionField($this, 'userIds', onlyNotEmptyValues: true),
		];

		$scope = $fields['scope'] ?? null;
		if ($scope === EntityEditorConfigScope::CUSTOM)
		{
			$validators[] = new IntegerField($this, 'customScopeId', 0);
		}

		return $validators;
	}
}
