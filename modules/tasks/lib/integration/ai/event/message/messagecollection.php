<?php

namespace Bitrix\Tasks\Integration\AI\Event\Message;

use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use IteratorAggregate;

class MessageCollection implements IteratorAggregate, Arrayable
{
	private array $items = [];

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	public function add(Message $message): static
	{
		$this->items[] = $message;
		return $this;
	}

	public function toArray(): array
	{
		return array_map(static fn (Message $message): array => $message->toArray(), $this->items);
	}
}
