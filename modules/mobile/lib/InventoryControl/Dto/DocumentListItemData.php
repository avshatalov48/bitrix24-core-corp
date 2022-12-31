<?php


namespace Bitrix\Mobile\InventoryControl\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\UI\SimpleList\Dto\Data;

final class DocumentListItemData extends Data
{
	/** @var string|null */
	public $docType;

	public $statuses = [];

	public function getCasts(): array
	{
		$casts = parent::getCasts();
		$casts['docType'] = Type::string();
		$casts['statuses'] = Type::collection(Type::string());
		return $casts;
	}
}
