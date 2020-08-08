<?php

namespace Bitrix\Location\Entity\Location;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Location;
use Bitrix\Main\ArgumentOutOfRangeException;

/** @internal */
final class Parents extends Collection
{
	/** @var Location[]  */
	protected $items = [];
	/** @var Location|null  */
	protected $descendant = null;

	/**
	 * @return Location
	 */
	public function getDescendant(): ?Location
	{
		return $this->descendant;
	}

	/**
	 * @param Location $descendant
	 * @return $this
	 */
	public function setDescendant(Location $descendant)
	{
		$this->descendant = $descendant;
		return $this;
	}

	/**
	 * @param array $locations
	 * @throws ArgumentOutOfRangeException
	 */
	public function setItems(array $locations)
	{
		foreach($locations as $location)
		{
			if(!($location instanceof Location))
			{
				throw new ArgumentOutOfRangeException('location');
			}

			$this->addItem($location);
		}
	}

	/**
	 * @param Location $location
	 * @return bool
	 */
	public function isContain(Location $location)
	{
		foreach($this->items as $item)
		{
			if($item->isEqualTo($location))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Parents $parents
	 * @return bool
	 * todo: case, then something was changed in chains. Partly matching.
	 */
	public function isEqualTo(Parents $parents)
	{
		if($this->count() != $parents->count())
		{
			return false;
		}

		/**
		 * @var  $idx
		 * @var Location $parent
		 */
		foreach($this as $idx => $item)
		{
			if(!$item->isEqualTo($parents[$idx]))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param int $type
	 * @return Location|null
	 */
	public function getItemByType(int $type):? Location
	{
		foreach($this->items as $item)
		{
			if($item->getType() === $type)
			{
				return $item;
			}
		}

		return null;
	}
}