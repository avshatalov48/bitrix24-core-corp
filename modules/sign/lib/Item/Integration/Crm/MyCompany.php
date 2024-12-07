<?php


namespace Bitrix\Sign\Item\Integration\Crm;

use Bitrix\Sign\Contract\Item;

final class MyCompany implements Item
{
	public function __construct(
		public string $name,
		public ?int $id = null,
		public ?string $taxId = null,
	)
	{
	}
}