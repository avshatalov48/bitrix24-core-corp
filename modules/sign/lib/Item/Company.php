<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract\Item;

class Company implements Item
{
	public function __construct(
		public int $id,
		public string $title,
		public ?string $rqInn = null,
		public ?string $registerUrl = null,
		/**
		 * @var array|CompanyProvider[]
		 */
		public array $providers = [],
	) {}
}
