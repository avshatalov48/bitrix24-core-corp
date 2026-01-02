<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use ArrayIterator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\EntityInterface;
use Bitrix\Main\Validation\Rule\Recursive\Validatable;

/**
 * @method array getIdList()
 */
abstract class AbstractEntityCollection implements EntityCollectionInterface
{
	/** @var EntityInterface[] */
	#[Validatable(iterable: true)]
	protected array $entities = [];

	abstract protected static function getEntityClass(): string;

	public function __construct(EntityInterface ...$entities)
	{
		foreach ($entities as $entity)
		{
			if ($entity::class !== static::getEntityClass())
			{
				throw new ArgumentException();
			}
		}

		$this->entities = $entities;
	}

	public function cloneWith(array $props): static
	{
		$entities = [];
		foreach ($this->entities as $entity)
		{
			if ($entity instanceof AbstractEntity)
			{
				$entities[] = $entity->cloneWith($props);
			}
		}

		return new static(...$entities);
	}

	public function findOneById(int $id, string $idKey = 'id'): ?EntityInterface
	{
		return $this->findOne([$idKey => $id]);
	}

	public function findAllByIds(array $ids, string $idKey = 'id'): ?static
	{
		return $this->findAll([$idKey => $ids]);
	}

	public function findOne(array $conditions): ?EntityInterface
	{
		foreach ($this as $item)
		{
			foreach ($conditions as $key => $value)
			{
				if (!property_exists($item, $key) || $item->{$key} !== $value)
				{
					continue 2;
				}
			}

			return $item;
		}

		return null;
	}

	public function findAll(array $conditions): static
	{
		$result = [];
		foreach ($this as $item)
		{
			foreach ($conditions as $key => $value)
			{
				if (!property_exists($item, $key))
				{
					continue 2;
				}

				if (is_array($value))
				{
					if (!in_array($item->{$key}, $value, true))
					{
						continue 2;
					}
				}
				elseif ($item->{$key} !== $value)
				{
					continue 2;
				}
			}
			$result[] = $item;
		}

		return new static(...$result);
	}

	public function add(EntityInterface $item): void
	{
		if (!$item instanceof EntityInterface)
		{
			throw new ArgumentException();
		}


		if ($item::class !== static::getEntityClass())
		{
			throw new ArgumentException();
		}

		$this->entities[] = $item;
	}

	public function remove(mixed $id): static
	{
		foreach ($this->entities as $i => $entity)
		{
			if ($entity->getId() === $id)
			{
				unset($this->entities[$i]);
			}
		}

		return $this;
	}

	public function __call(string $name, array $args = []): ?array
	{
		$operation = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));
		$subOperation = lcfirst(substr($property, -4));

		if ($operation === 'get' && $subOperation === 'list')
		{
			$property = substr($property, 0, -4);

			return array_column($this->toArray(), $property);
		}

		return null;
	}

	public function getIds(): array
	{
		return $this->getIdList();
	}

	public static function mapFromArray(array $props): static
	{
		$entities = [];
		foreach ($props as $prop)
		{
			if (!is_array($prop))
			{
				continue;
			}

			/** @var EntityInterface $itemClass */
			$itemClass = static::getEntityClass();
			$entities[] = $itemClass::mapFromArray($prop);
		}

		return new static(...$entities);
	}

	public static function mapFromIds(array $ids, string $idKey = 'id'): static
	{
		$entities = [];
		foreach ($ids as $id)
		{
			/** @var EntityInterface $itemClass */
			$itemClass = static::getEntityClass();
			if (is_subclass_of($itemClass, AbstractEntity::class))
			{
				$entities[] = $itemClass::mapFromId(id: $id, idKey: $idKey);
			}
			else
			{
				$entities[] = $itemClass::mapFromArray([$idKey => $id]);
			}
		}

		return new static(...$entities);
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->entities);
	}

	public function toArray(): array
	{
		return array_map(static fn ($collectionItem): array => $collectionItem->toArray(), $this->entities);
	}

	public function getEntities(): array
	{
		return $this->entities;
	}

	public function diff(self $collectionToCompare): static
	{
		$ids = array_diff($this->getIds(), $collectionToCompare->getIds());

		$entities = $this->filter(
			static fn (EntityInterface $collectionItem): bool
				=> in_array($collectionItem->getId(), $ids, true)
		);

		return new static(...$entities);
	}

	public function count(): int
	{
		return count($this->entities);
	}

	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function getFirstEntity(): ?EntityInterface
	{
		if (!$this->count())
		{
			return null;
		}

		return $this->entities[0];
	}

	public function merge(self $collection): static
	{
		foreach ($collection as $item)
		{
			$this->add($item);
		}

		return $this;
	}

	public function find(callable $callback): ?EntityInterface
	{
		foreach ($this->entities as $key => $item)
		{
			if ($callback($item, $key))
			{
				return $item;
			}
		}

		return null;
	}

	public function filter(callable $callback): static
	{
		return new static(...array_filter($this->entities, $callback));
	}

	public function map(callable $callback): array
	{
		return array_map($callback, $this->entities);
	}
}
