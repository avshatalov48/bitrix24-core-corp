<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Member;

use Bitrix\Sign\Contract;

class Channel implements Contract\Item
{
	public function __construct(public string $type, public ?string $value)
	{
	}
}