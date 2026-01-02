<?php

namespace Bitrix\Crm\UserField\Dto;

use Bitrix\Crm\Dto\Caster;
use Bitrix\Crm\Dto\Dto;

class EnumerationList extends Dto
{
	/** @var EnumerationItem[] $items */
	public array $items;

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return match ($propertyName){
			'items' => new Caster\CollectionCaster(new Caster\ObjectCaster(EnumerationItem::class)),
			default => null,
		};
	}
}
