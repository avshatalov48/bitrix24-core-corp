<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<Binding>
 */
final class BindingCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return Binding::class;
	}
}