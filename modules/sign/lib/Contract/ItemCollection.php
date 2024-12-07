<?php

namespace Bitrix\Sign\Contract;

use Bitrix\Sign\Contract;

/**
 * @template T of Contract\Item
 */
interface ItemCollection
{
	/**
	 * @return T[]
	 */
	public function toArray(): array;
}