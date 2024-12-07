<?php

namespace Bitrix\Sign\Item\Api\Property\Response\Signing\Configure;

use Bitrix\Sign\Contract;

class Member implements Contract\Item
{
	public string $uid;
	public string $key;

	public function __construct(string $uid, string $key)
	{
		$this->key = $key;
		$this->uid = $uid;
	}
}