<?php

namespace Bitrix\Crm\Activity\Mail\Attachment\Dto;

/**
 * @implements \IteratorAggregate<int, StorageAttachmentId>
 */
class StorageAttachmentIdCollection implements \IteratorAggregate, \Countable
{
	private array $items = [];

	public function append(StorageAttachmentId $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * @return list<int>
	 */
	public function getStorageElementIds(): array
	{
		return array_map(fn(StorageAttachmentId $attachmentId): int => $attachmentId->storageElementId, $this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}
}