<?php

namespace Bitrix\Sign\Item\Api\Mobile\Signing;

use Bitrix\Sign\Item;

class ExternalUrlResponse extends Item\Api\Response
{
	public function __construct(
		public string $url = ''
	) {}
}