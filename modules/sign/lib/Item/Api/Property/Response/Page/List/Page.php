<?php

namespace Bitrix\Sign\Item\Api\Property\Response\Page\List;

use Bitrix\Sign\Contract;

class Page implements Contract\Item
{
	public string $url;

	public function __construct(string $url)
	{
		$this->url = $url;
	}
}