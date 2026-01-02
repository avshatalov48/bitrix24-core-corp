<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Sign\Item;
use Bitrix\Sign\Type;
use Bitrix\Sign\Blanks\Block\Configuration;

class ExternalId extends Configuration
{
	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		if ($document->externalIdSourceType === Type\Document\ExternalIdSourceType::MANUAL)
		{
			return [
				'show' => true,
				'text' => $document->externalId ?? '',
			];
		}

		return [
			'text' => ''
		];
	}
}