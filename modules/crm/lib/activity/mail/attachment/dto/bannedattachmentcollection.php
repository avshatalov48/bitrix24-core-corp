<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

/**
 * @implements \IteratorAggregate<int, BannedAttachment>
 */
class BannedAttachmentCollection implements \IteratorAggregate, \Countable
{
	private array $items = [];

	public function append(BannedAttachment $attachment): static
	{
		$this->items[] = $attachment;

		return $this;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}
}