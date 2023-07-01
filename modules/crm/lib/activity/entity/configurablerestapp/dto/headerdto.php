<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;

final class HeaderDto extends Dto
{
	private const MAX_TAGS_COUNT = 2;

	public ?TextWithTranslationDto $title = null;
	public ?ActionDto $titleAction = null;
	public ?array $tags = null;

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		switch ($propertyName)
		{
			case 'tags':
				return new \Bitrix\Crm\Dto\Caster\CollectionCaster(new \Bitrix\Crm\Dto\Caster\ObjectCaster(TagDto::class));
		}

		return null;
	}

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\RequiredField($this, 'title'),
			new Validator\TextWithTranslationField($this, 'title'),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'titleAction'),
			new \Bitrix\Crm\Dto\Validator\ObjectCollectionField($this, 'tags', self::MAX_TAGS_COUNT),
		];
	}
}
