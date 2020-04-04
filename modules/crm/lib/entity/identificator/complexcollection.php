<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Crm\Entity\Identificator;

use Bitrix\Main\Type\Dictionary;

/**
 * Class ComplexCollection
 *
 * @package Bitrix\Crm\Entity\Identificator
 */
class ComplexCollection extends Dictionary
{
	/**
	 * Constructor ComplexCollection.
	 * @param Complex[] $values Initial entities in the collection.
	 */
	public function __construct(array $values = null)
	{
		if($values)
		{
			$this->add($values);
		}
	}

	/**
	 * Add an array of complex identificators to the collection.
	 *
	 * @param Complex[] $items Complex identificators.
	 * @param bool $uniqueOnly Add unique entity only.
	 * @return void
	 */
	public function add(array $items, $uniqueOnly = false)
	{
		foreach($items as $complex)
		{
			$this->setComplex($complex, $uniqueOnly);
		}
	}

	/**
	 * Add entity to the collection.
	 *
	 * @param int|null $entityTypeId Entity type ID.
	 * @param int|null $entityId Entity ID.
	 * @param bool $uniqueOnly Add unique entity only.
	 * @return void
	 */
	public function addIdentificator($entityTypeId, $entityId, $uniqueOnly = false)
	{
		if (!Complex::validateId($entityTypeId))
		{
			return;
		}
		if (!Complex::validateId($entityId))
		{
			return;
		}

		$this->setComplex(new Complex($entityTypeId, $entityId), $uniqueOnly);
	}

	/**
	 * Return true if collection has complex identificator.
	 *
	 * @param Complex $comparableComplex Comparable complex identificator.
	 * @return bool
	 */
	public function hasComplex(Complex $comparableComplex)
	{
		foreach ($this->toArray() as $complex)
		{
			if (Complex::isEqual($complex, $comparableComplex))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Add an complex identificator to the collection.
	 *
	 * @param Complex $complex An entity object.
	 * @param bool $uniqueOnly Add unique entity only.
	 * @param int $offset Offset in the array.
	 * @return void
	 */
	public function setComplex(Complex $complex, $uniqueOnly = false, $offset = null)
	{
		if ($uniqueOnly && $this->hasComplex($complex))
		{
			return;
		}

		parent::offsetSet($offset, $complex);
	}

	/**
	 * Get first complex identificator by type ID.
	 *
	 * @param int $typeId Type ID.
	 * @return Complex|null
	 */
	public function getComplexByTypeId($typeId)
	{
		foreach ($this->toArray() as $complex)
		{
			if ($complex->getTypeId() === $typeId)
			{
				return $complex;
			}
		}

		return null;
	}

	/**
	 * Get first ID by type ID.
	 *
	 * @param int $typeId Type ID.
	 * @return int|null
	 */
	public function getIdByTypeId($typeId)
	{
		$complex = $this->getComplexByTypeId($typeId);
		return $complex ? $complex->getId() : null;
	}

	/**
	 * \ArrayAccess thing.
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->setComplex($value, $offset);
	}

	/**
	 * Computes the difference of this and argument collections.
	 * Compares $this against $collection and returns the values in $this that are not present in $collection.
	 *
	 * @param ComplexCollection $collection Collection.
	 * @return static
	 */
	public function diff(ComplexCollection $collection)
	{
		$result = new static();
		foreach($this->toArray() as $complex)
		{
			if ($collection->hasComplex($complex))
			{
				continue;
			}

			$result->setComplex(clone $complex);
		}

		return $result;
	}

	/**
	 * Convert to array of identificators.
	 *
	 * @param array $keys Keys of array.
	 * @return array
	 */
	public function toSimpleArray(array $keys = ['ENTITY_TYPE_ID', 'ENTITY_ID'])
	{
		return array_map(
			function ($item) use ($keys)
			{
				/** @var Complex $item */
				return $item->toArray($keys);
			},
			$this->toArray()
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return Complex[]
	 */
	public function toArray()
	{
		return parent::toArray();
	}
}
