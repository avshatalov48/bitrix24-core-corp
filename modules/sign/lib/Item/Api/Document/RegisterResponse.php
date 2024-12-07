<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Item;

class RegisterResponse extends Item\Api\Response
{
	public string $uid;

	public function __construct(string $uid)
	{
		$this->uid = $uid;
	}
}