<?php

namespace Bitrix\Sign\Item\Api\Document\Signing;

use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Api\Property;

class ConfigureResponse extends Item\Api\Response
{
	public Property\Response\Signing\Configure\MemberCollection $members;

	public function __construct(Property\Response\Signing\Configure\MemberCollection $members)
	{
		$this->members = $members;
	}
}