<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<Group>
 */
final class GroupCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return Group::class;
	}
}