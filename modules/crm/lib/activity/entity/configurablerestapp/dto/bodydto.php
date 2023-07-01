<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;

final class BodyDto extends Dto
{
	private const MAX_BLOCKS_COUNT = 20;

	public ?LogoDto $logo = null;
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
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'logo'),
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'blocks'),
			new \Bitrix\Crm\Dto\Validator\ObjectCollectionField($this, 'blocks', self::MAX_BLOCKS_COUNT),
		];
	}
}
