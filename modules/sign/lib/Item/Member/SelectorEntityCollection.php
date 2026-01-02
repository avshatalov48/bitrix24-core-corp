<?php

namespace Bitrix\Sign\Item\Member;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<SelectorEntity>
 */
class SelectorEntityCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return SelectorEntity::class;
	}
}