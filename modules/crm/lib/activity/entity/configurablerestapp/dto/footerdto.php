<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;

final class FooterDto extends Dto
{
	private const MAX_BUTTONS_COUNT = 2;

	public ?array $buttons = null;
	public ?bool $showNote = null;
	public ?FooterMenuDto $menu = null;

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		switch ($propertyName)
		{
			case 'buttons':
				return new \Bitrix\Crm\Dto\Caster\CollectionCaster(new \Bitrix\Crm\Dto\Caster\ObjectCaster(FooterButtonDto::class));
		}

		return null;
	}

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\ObjectCollectionField($this, 'buttons', self::MAX_BUTTONS_COUNT),
			new \Bitrix\Crm\Dto\Validator\ObjectField($this, 'menu'),
		];
	}
}
