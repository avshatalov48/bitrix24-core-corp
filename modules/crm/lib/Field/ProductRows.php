<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Result;

final class ProductRows extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		return $item->normalizeProductRows();
	}
}
