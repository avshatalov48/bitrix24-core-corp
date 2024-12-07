<?php

namespace Bitrix\SignMobile\Contract\Response;

use Bitrix\Sign\Contract\ItemCollection;
use Countable;
use IteratorAggregate;

interface ResourceCollectionContract extends Countable, IteratorAggregate
{
	public static function fromItemCollection(ItemCollection $collection): self;
}