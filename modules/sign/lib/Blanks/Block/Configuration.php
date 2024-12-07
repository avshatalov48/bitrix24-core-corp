<?php

namespace Bitrix\Sign\Blanks\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Type;
Loc::loadMessages(__DIR__ . '/../../blank/block.php');

abstract class Configuration
{
	public const VIEW_SPECIFIC_DATA_KEY = '__view';

	abstract function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array;

	public function validate(Item\Block $block): Main\Result
	{
		$result = new Main\Result();

		if (!in_array($block->code, Type\BlockCode::getAll()))
		{
			return $result->addError(new Main\Error('Invalid block code'));
		}

		return $result;
	}

	public function getViewSpecificData(Item\Block $block): ?array
	{
		return null;
	}
}