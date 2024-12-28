<?php

namespace Bitrix\Sign\Item\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Exception\ObjectNotFoundException;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service\Container;

final class Document implements Contract\Item, AccessibleItem, Contract\Access\AccessibleItemWithOwner, Contract\Item\ItemWithCrmId
{
	public function __construct(
		private readonly Item\Document $document,
	)
	{
	}

	/**
	 * @throws ObjectNotFoundException
	 */
	public static function createFromId(int $itemId): self
	{
		$document = Container::instance()->getDocumentRepository()->getById($itemId);

		return $document
			? new self($document)
			: throw new ObjectNotFoundException('Document not found')
		;
	}

	public function getId(): int
	{
		return $this->document->getId();
	}

	public function getOwnerId(): int
	{
		return $this->document->getOwnerId();
	}

	public function getCrmId(): int
	{
		return $this->document->getCrmId();
	}

	public function isTemplated(): bool
	{
		return $this->document->isTemplated();
	}
}
