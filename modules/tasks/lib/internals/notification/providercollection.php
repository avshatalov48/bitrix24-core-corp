<?php

namespace Bitrix\Tasks\Internals\Notification;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class ProviderCollection implements IteratorAggregate, Countable
{
	private ?ArrayIterator $iterator = null;

	/** @var ProviderInterface[]  */
	private array $providers;

	public function __construct(ProviderInterface ...$providers)
	{
		$this->providers = $providers;
	}

	public function getIterator(): ArrayIterator
	{
		if (is_null($this->iterator))
		{
			$this->iterator = new ArrayIterator($this->providers);
		}

		return $this->iterator;
	}

	public function add(ProviderInterface $provider): void
	{
		$this->providers[] = $provider;
	}

	public function isEmpty(): bool
	{
		return empty($this->providers);
	}

	public function count(): int
	{
		return count($this->providers);
	}

	public function current(): ProviderInterface
	{
		return $this->getIterator()->current();
	}
}