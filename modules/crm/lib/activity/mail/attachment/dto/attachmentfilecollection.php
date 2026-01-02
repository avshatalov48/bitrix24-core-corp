<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

/**
 * @implements \IteratorAggregate<int, AttachmentFile>
 */
class AttachmentFileCollection implements \IteratorAggregate
{
	private array $items = [];

	public function append(AttachmentFile $file): static
	{
		$this->items[] = $file;

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
}