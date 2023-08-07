<?php


namespace Bitrix\CatalogMobile\InventoryControl\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\UI\SimpleList\Dto\Item;

final class DocumentListItem extends Item
{
	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'data' => Type::object(DocumentListItemData::class),
		];
	}
}
