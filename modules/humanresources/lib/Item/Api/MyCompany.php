<?php

namespace Bitrix\HumanResources\Item\Api;

use Bitrix\HumanResources\Contract\Item;

class MyCompany implements Item
{
	public ?int $id = null;
	public ?string $title = null;
	public ?string $providerType = null;
	public ?string $providerId = null;
}