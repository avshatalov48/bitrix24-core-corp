<?php

namespace Bitrix\Sign\Item\Api\Client;

class DomainRequest implements \Bitrix\Sign\Contract\Item
{
	public string $domain;

	public function __construct(string $domain)
	{
		$this->domain = $domain;
	}
}
