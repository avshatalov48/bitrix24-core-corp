<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

/**
 * Class HandlersCollection
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class HandlersCollection implements \IteratorAggregate
{
	/** @var HandlerContract[] */
	protected $items = [];

	/**
	 * @param HandlerContract $item
	 */
	public function add(HandlerContract $item)
	{
		$this->items[] = $item;
	}

	/**
	 * @param callable|null $filter
	 * @return HandlerContract[]
	 */
	public function getItems($filter = null)
	{
		if (is_null($filter) || !is_callable($filter))
		{
			return $this->items;
		}

		return array_filter($this->items, $filter);
	}

	/**
	 * @return HandlerContract[]
	 */
	public function getInstallableItems()
	{
		return $this->getItems(
			function (HandlerContract $item)
			{
				return $item->isInstallable();
			}
		);
	}

	/**
	 * @return bool
	 */
	public function hasInstallableItems(): bool
	{
		return count($this->getInstallableItems()) > 0;
	}

	/**
	 * @return HandlerContract[]
	 */
	public function getInstalledItems()
	{
		return $this->getItems(
			function (HandlerContract $item)
			{
				return $item->isInstalled();
			}
		);
	}

	/**
	 * @return HandlerContract[]
	 */
	public function getInstallableInstalledItems()
	{
		return $this->getItems(
			function (HandlerContract $item)
			{
				return $item->isInstalled() && $item->isInstallable();
			}
		);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->items);
	}
}
