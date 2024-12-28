<?php

namespace Bitrix\HumanResources\Item\Api;

use Bitrix\HumanResources\Contract\Item;

class MemberProvider implements Item
{
	public int $id;
	public array $data = [];
}