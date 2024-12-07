<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract;

class User implements Contract\Item
{
	public function __construct(
		public int $id,
		public ?string $firstName = null,
		public ?string $lastName = null,
		public ?string $secondName = null,
		public ?int $personalPhotoId = null,
	){}
}