<?php

namespace Bitrix\Tasks\Flow;

use ArrayAccess;
use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Countable;
use IteratorAggregate;

/**
 * @method array getIdList()
 * @method array getOwnerIdList()
 * @method array getGroupIdList()
 */

class FlowCollection implements IteratorAggregate, Arrayable, Countable, ArrayAccess
{
	/** @var Flow[]  */
	private array $flows = [];

	public function __construct(Flow ...$flows)
	{
		foreach ($flows as $flow)
		{
			$this->flows[$flow->getId()] = $flow;
		}
	}

	public function add(Flow $flow): static
	{
		$this->flows[$flow->getId()] = $flow;
		return $this;
	}

	/** @return Flow[] */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->flows);
	}

	public function toArray(): array
	{
		return array_map(static fn (Flow $flow): array => $flow->toArray(), $this->flows);
	}

	public function getFlows(): array
	{
		return $this->flows;
	}

	public function count(): int
	{
		return count($this->flows);
	}

	public function isEmpty(): bool
	{
		return 0 === $this->count();
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->flows[$offset]);
	}

	public function offsetGet(mixed $offset): ?Flow
	{
		return $this->flows[$offset] ?? null;
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->flows[$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->flows[$offset]);
	}

	public function __call(string $name, array $args)
	{
		$operation = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));

		if ($operation === 'get')
		{
			$isList = lcfirst(substr($property, -4)) === 'list';
			$property = $isList ? substr($property, 0, -4) : $property;

			return array_column($this->toArray(), $property);
		}

		return null;
	}

	public function filter(FlowDistributionType $distributionType): FlowCollection
	{
		$flows = array_filter(
			$this->flows,
			static fn (Flow $flow): bool => $flow->getDistributionType() === $distributionType
		);

		return new FlowCollection(...$flows);
	}
}