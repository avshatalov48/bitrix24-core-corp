<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Sign\Item;
use Bitrix\Sign\Blanks\Block\Configuration;

class Stamp extends Configuration
{
	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		return [];
	}
}
