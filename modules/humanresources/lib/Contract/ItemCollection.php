<?php

namespace Bitrix\HumanResources\Contract;

use Bitrix\HumanResources\Exception\WrongStructureItemException;

/**
 * @extends	 \IteratorAggregate<T, V>
 * @template T
 * @template V of Item
 */
interface ItemCollection extends \IteratorAggregate
{
	/**
	 * @psalm-param V $item
	 *
	 * @throws WrongStructureItemException
	 */
	public function add(Item $item): static;
}