<?php

namespace Bitrix\Sign\Item\Api\Member;

use Bitrix\Sign\Item\Api\Response;

class WebStatusResponse extends Response
{
	public function __construct(public ?string $status = null) {}
}