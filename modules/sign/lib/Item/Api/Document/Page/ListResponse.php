<?php

namespace Bitrix\Sign\Item\Api\Document\Page;

use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Api\Property;

class ListResponse extends Item\Api\Response
{
	public bool $ready;
	public Property\Response\Page\List\PageCollection $pages;

	public function __construct(bool $ready, Property\Response\Page\List\PageCollection $pages)
	{
		$this->ready = $ready;
		$this->pages = $pages;
	}
}