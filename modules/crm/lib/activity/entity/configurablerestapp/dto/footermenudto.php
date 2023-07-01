<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Dto\Dto;

final class FooterMenuDto extends Dto
{
	private const MAX_MENU_ITEMS_COUNT = 10;
	public ?bool $showPinItem = null;
	public ?bool $showPostponeItem = null;
	public ?bool $showDeleteItem = null;
	public ?array $items = null;

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		switch ($propertyName)
		{
			case 'items':
				return new \Bitrix\Crm\Dto\Caster\CollectionCaster(new \Bitrix\Crm\Dto\Caster\ObjectCaster(MenuItemDto::class));
		}

		return null;
	}

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\ObjectCollectionField($this, 'items', self::MAX_MENU_ITEMS_COUNT),
		];
	}
}
