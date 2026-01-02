<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\NotEmptyField;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\StringField;
use Bitrix\Crm\EO_Status_Collection;

final class CreateWithStagesParameters extends Dto
{
	public string $name;

	/** @var Stage[] */
	public array $stages;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName) {
			'stages' => new Caster\CollectionCaster(new Caster\ObjectCaster(Stage::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new StringField($this, 'name'),
			new NotEmptyField($this, 'name'),

			new ObjectCollectionField($this, 'stages'),
			new NotEmptyField($this, 'stages'),
		];
	}

	public function getStageOrmCollection(): EO_Status_Collection
	{
		$stageCollection = new EO_Status_Collection();
		foreach ($this->stages as $stage)
		{
			$stageCollection->add($stage->toOrmObject());
		}

		return $stageCollection;
	}
}
