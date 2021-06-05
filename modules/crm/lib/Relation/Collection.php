<?php

namespace Bitrix\Crm\Relation;

use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;

class Collection implements \Iterator, \Countable
{
	/** @var Relation[] */
	protected $relations = [];

	/**
	 * Collection constructor.
	 *
	 * @param Relation[] $relations
	 */
	public function __construct(array $relations = [])
	{
		foreach ($relations as $relation)
		{
			$this->add($relation);
		}
	}

	/**
	 * Returns a new collection that contains all unique relations from this and the provided collections
	 *
	 * @param Collection $collection
	 *
	 * @return Collection
	 */
	public function merge(Collection $collection): Collection
	{
		$result = new static($this->relations);

		foreach ($collection as $relation)
		{
			$result->add($relation);
		}

		return $result;
	}

	/**
	 * Add the relation to the collection
	 * If a similar relation already present in the collection, the new one will not be added
	 * But if the new relation is predefined, it will replace the old one
	 *
	 * @param Relation $relation
	 *
	 * @return $this
	 */
	public function add(Relation $relation): Collection
	{
		$existingRelation = $this->get($relation->getIdentifier());

		if (!$existingRelation)
		{
			//this relation is entirely new. simply add it
			$this->relations[] = $relation;

			return $this;
		}

		if ($relation->isPredefined() && !$existingRelation->isPredefined())
		{
			//replace old one with the new relation
			/** @var int $index */
			$index = array_search($existingRelation, $this->relations, true);
			$this->relations[$index] = $relation;
		}

		return $this;
	}

	/**
	 * Returns a relation specified by the identifier
	 * If such a relation does not exist in the collection, returns null
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return Relation|null
	 */
	public function get(RelationIdentifier $identifier): ?Relation
	{
		foreach ($this->relations as $index => $relation)
		{
			if ($relation->hasEqualIdentifier($identifier))
			{
				return $relation;
			}
		}

		return null;
	}

	/**
	 * Remove a relation from the collection
	 *
	 * @param RelationIdentifier $identifier
	 *
	 * @return $this
	 */
	public function remove(RelationIdentifier $identifier): Collection
	{
		$existingRelation = $this->get($identifier);
		if ($existingRelation)
		{
			/** @var int $index */
			$index = array_search($existingRelation, $this->relations, true);
			unset($this->relations[$index]);
		}

		return $this;
	}

	/**
	 * Returns a new collection filtered by
	 * Relation::getParentEntityTypeId === $parentEntityTypeId OR Relation::getChildEntityTypeId === $childEntityTypeId
	 *
	 * Relation objects are NOT cloned
	 *
	 * @param int $entityTypeId
	 *
	 * @return Collection
	 */
	public function filterByEntityTypeId(int $entityTypeId): Collection
	{
		$filteredCollection = new static();

		foreach ($this->relations as $relation)
		{
			if (
				($relation->getParentEntityTypeId() === $entityTypeId)
				|| ($relation->getChildEntityTypeId() === $entityTypeId)
			)
			{
				$filteredCollection->add($relation);
			}
		}

		return $filteredCollection;
	}

	/**
	 * Returns a new collection filtered by Relation::getParentEntityTypeId === $parentEntityTypeId
	 *
	 * Relation objects are NOT cloned
	 *
	 * @param int $parentEntityTypeId
	 *
	 * @return Collection
	 */
	public function filterByParentEntityTypeId(int $parentEntityTypeId): Collection
	{
		$filteredCollection = new static();

		foreach ($this->relations as $relation)
		{
			if ($relation->getParentEntityTypeId() === $parentEntityTypeId)
			{
				$filteredCollection->add($relation);
			}
		}

		return $filteredCollection;
	}

	/**
	 * Returns a new collection filtered by Relation::getChildEntityTypeId === $childEntityTypeId
	 *
	 * Relation objects are NOT cloned
	 *
	 * @param int $childEntityTypeId
	 *
	 * @return Collection
	 */
	public function filterByChildEntityTypeId(int $childEntityTypeId): Collection
	{
		$filteredCollection = new static();

		foreach ($this->relations as $index => $relation)
		{
			if ($relation->getChildEntityTypeId() === $childEntityTypeId)
			{
				$filteredCollection->add($relation);
			}
		}

		return $filteredCollection;
	}

	/**
	 * Returns a new collection where not predefined relations are removed
	 *
	 * Relation objects are NOT cloned
	 *
	 * @return Collection
	 */
	public function filterOutCustomRelations(): Collection
	{
		$filteredCollection = new static();

		foreach ($this->relations as $index => $relation)
		{
			if ($relation->isPredefined())
			{
				$filteredCollection->add($relation);
			}
		}

		return $filteredCollection;
	}

	/**
	 * Returns a new collection where predefined relations are removed
	 *
	 * Relation objects are NOT cloned
	 *
	 * @return Collection
	 */
	public function filterOutPredefinedRelations(): Collection
	{
		$filteredCollection = new static();

		foreach ($this->relations as $index => $relation)
		{
			if (!$relation->isPredefined())
			{
				$filteredCollection->add($relation);
			}
		}

		return $filteredCollection;
	}

	/**
	 * Transform this collection to array
	 *
	 * @return Relation[]
	 */
	public function toArray(): array
	{
		return $this->relations;
	}

	/**
	 * Returns true if this collection contains no relations
	 *
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return ($this->count() === 0);
	}

	//region Iterator implementation
	/**
	 * @inheritDoc
	 */
	public function current(): ?Relation
	{
		return current($this->relations);
	}

	/**
	 * @inheritDoc
	 */
	public function next(): void
	{
		next($this->relations);
	}

	/**
	 * @inheritDoc
	 */
	public function key(): int
	{
		return key($this->relations);
	}

	/**
	 * @inheritDoc
	 */
	public function valid(): bool
	{
		return (key($this->relations) !== null);
	}

	/**
	 * @inheritDoc
	 */
	public function rewind(): void
	{
		reset($this->relations);
	}
	//endregion

	/**
	 * @inheritDoc
	 */
	public function count(): int
	{
		return count($this->relations);
	}
}
