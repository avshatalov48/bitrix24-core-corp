<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Main;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Item;

class HcmLinkReference extends Configuration
{
	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		return $block->data ?? ['text' => ''];
	}

	public function validate(Item\Block $block): Main\Result
	{
		$result = parent::validate($block);

		$field = $block->data['field'] ?? null;
		if (!$field)
		{
			return $result->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_HCMLINK_REFERENCE_ERROR_FIELD_NOT_SELECTED'),
					'HCMLINK_REFERENCE_ERROR_FIELD_NOT_SELECTED',
				)
			);
		}

		return $result;
	}
}
