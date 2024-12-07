<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Item;

class Text extends Configuration
{
	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		return ['text' => $block->data['text'] ?? ''];
	}
}