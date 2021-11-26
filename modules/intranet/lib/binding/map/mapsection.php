<?php

namespace Bitrix\Intranet\Binding\Map;

use Bitrix\Main\ArgumentException;

class MapSection
{
	/** @var string */
	protected $scope;
	/** @var string */
	protected $code;

	/** @var MapItem[] */
	protected $items = [];

	public function __construct(string $scope, string $code)
	{
		$this->scope = $scope;
		$this->code = $code;
	}

	public function getScope(): string
	{
		return $this->scope;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function add(MapItem $item): self
	{
		if ($this->has($item))
		{
			throw new ArgumentException('A MapItem with the same code already exists in the section');
		}

		$this->items[$item->getCode()] = $item;

		return $this;
	}

	public function has(MapItem $item): bool
	{
		return $this->hasByCode($item->getCode());
	}

	public function hasByCode(string $mapItemCode): bool
	{
		return isset($this->items[$mapItemCode]);
	}

	public function remove(MapItem $item): self
	{
		if (!$this->has($item))
		{
			throw new ArgumentException('The item that is being removed does not exist in this section');
		}

		unset($this->items[$item->getCode()]);

		return $this;
	}

	/**
	 * @return MapItem[]
	 */
	public function getItems(): array
	{
		return array_values($this->items);
	}

	/**
	 * Returns true if this MapSection represents the same section as another MapSection object.
	 * Two object can have different sets of items, but still represent the same section.
	 * Only aspects that matter are scope and code
	 *
	 * @param static $anotherSection
	 *
	 * @return bool
	 */
	public function isSimilarTo(self $anotherSection): bool
	{
		return $this->getSimilarHash() === $anotherSection->getSimilarHash();
	}

	/**
	 * Returns string that is associated with this object. Two strings will be equal if the object are similar
	 *
	 * @return string
	 */
	public function getSimilarHash(): string
	{
		return ($this->getScope() . '_' . $this->getCode());
	}

	/**
	 * Returns true if this MapSection is exactly the same as another MapSection.
	 * Only contained data is taken into consideration. Two object could be equal even
	 * if they are the different instances, but contain the same data.
	 *
	 * @param static $anotherSection
	 *
	 * @return bool
	 */
	public function isEqualTo(self $anotherSection): bool
	{
		if ($this->getScope() !== $anotherSection->getScope())
		{
			return false;
		}

		if ($this->getCode() !== $anotherSection->getCode())
		{
			return false;
		}

		// another section should have all items of this section
		foreach ($this->getItems() as $itemOfThisSection)
		{
			if (!$anotherSection->hasByCode($itemOfThisSection->getCode()))
			{
				return false;
			}
		}

		// this section should have all items of another section
		foreach ($anotherSection->getItems() as $itemOfAnotherSection)
		{
			if (!$this->hasByCode($itemOfAnotherSection->getCode()))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a new MapSection object that contains items from this and another section. Duplicates are skipped
	 *
	 * @param static $anotherSection
	 *
	 * @return static
	 * @throws ArgumentException
	 */
	public function merge(self $anotherSection): self
	{
		if (($anotherSection->getScope() !== $this->getScope()) || ($anotherSection->getCode() !== $this->getCode()))
		{
			throw new ArgumentException(
				'Merge of ' . static::class . ' object is possible only when two objects have the same scope and code'
			);
		}

		$result = new static($this->getScope(), $this->getCode());

		foreach (array_merge($this->getItems(), $anotherSection->getItems()) as $item)
		{
			if (!$result->hasByCode($item->getCode()))
			{
				$newItem = new MapItem($item->getCode(), $item->getCustomRestPlacementCode());

				$result->add($newItem);
			}
		}

		return $result;
	}
}
