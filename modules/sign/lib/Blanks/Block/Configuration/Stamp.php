<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;
use Bitrix\Sign\Blanks\Block\Configuration;

class Stamp extends Configuration
{
	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		return [];
	}
}