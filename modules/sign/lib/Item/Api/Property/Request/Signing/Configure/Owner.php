<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class Owner implements Contract\Item
{
	public function __construct(
		public string $name,
		public ?string $companyName = null,
		public ?string $channelType = null,
		public ?string $channelValue = null,
		public ?string $address = null,
	)
	{}
}