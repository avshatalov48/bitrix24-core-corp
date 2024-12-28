<?php

namespace Bitrix\Sign\Item\Field;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<FieldValue>
 */
class FieldValueCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return FieldValue::class;
	}
}