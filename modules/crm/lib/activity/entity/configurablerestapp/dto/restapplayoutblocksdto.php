<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Caster\CollectionCaster;
use Bitrix\Crm\Dto\Caster\ObjectCaster;
use Bitrix\Crm\Dto\Dto;
use Bitrix\Crm\Dto\Validator\ObjectCollectionField;
use Bitrix\Crm\Dto\Validator\RequiredField;
use Bitrix\Crm\Timeline\Entity\Repository\RestAppLayoutBlocksRepository;

class RestAppLayoutBlocksDto extends Dto
{
	/** @var ContentBlockDto[]|null  */
	public array|null $blocks = null;

	public function getCastByPropertyName(string $propertyName): Caster|null
	{
		return match ($propertyName) {
			'blocks' => new CollectionCaster(new ObjectCaster(ContentBlockDto::class)),
			default => null,
		};
	}

	protected function getValidators(array $fields): array
	{
		return [
			new RequiredField($this, 'blocks'),
			new ObjectCollectionField($this, 'blocks', RestAppLayoutBlocksRepository::MAX_LAYOUT_BLOCKS_COUNT),
		];
	}
}
