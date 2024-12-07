<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Api\Property;
use Bitrix\Sign\Item\Api\Property\Response\Page\List\Page;

class SignedFileLoadResponse extends Item\Api\Response
{
	public function __construct(public bool $ready, public ?Page $file = null)
	{}
}