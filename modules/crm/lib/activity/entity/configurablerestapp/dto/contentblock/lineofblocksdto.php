<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;


final class LineOfBlocksDto extends BaseContentBlockDto
{
	private const MAX_BLOCKS_COUNT = 20;

	public ?array $blocks = null;

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		switch ($propertyName)
		{
			case 'blocks':
				return new \Bitrix\Crm\Dto\Caster\CollectionCaster(new \Bitrix\Crm\Dto\Caster\ObjectCaster(ContentBlockDto::class));
		}

		return null;
	}

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'blocks'),
			new \Bitrix\Crm\Dto\Validator\ObjectCollectionField($this, 'blocks', self::MAX_BLOCKS_COUNT),
			new Dto\Validator\SimpleContentBlockField($this, 'blocks', true),
		];
	}
}
