<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlockDto;

class RestAppLayoutBlocksModel
{
	public function __construct(
		private int $itemTypeId,
		private int $itemId,
		private string $clientId,
		/** @var ContentBlockDto[] $contentBlocks */
		private array $contentBlocks = [],
	)
	{
	}

	public static function createFromArray(array $data): self
	{
		return new self(
			(int)$data['ITEM_TYPE'],
			(int)$data['ITEM_ID'],
			$data['CLIENT_ID'],
			$data['LAYOUT_BLOCKS'] ?? [],
		);
	}

	public function getItemTypeId(): int
	{
		return $this->itemTypeId;
	}

	public function setItemTypeId(int $itemTypeId): self
	{
		$this->itemTypeId = $itemTypeId;

		return $this;
	}

	public function getItemId(): int
	{
		return $this->itemId;
	}

	public function setItemId(int $itemId): self
	{
		$this->itemId = $itemId;

		return $this;
	}

	public function getClientId(): string
	{
		return $this->clientId;
	}

	public function setClientId(string $clientId): self
	{
		$this->clientId = $clientId;

		return $this;
	}

	/**
	 * @return ContentBlockDto[]
	 */
	public function getContentBlocks(): array
	{
		return $this->contentBlocks;
	}

	/**
	 * @param ContentBlockDto[] $contentBlocks
	 * @return $this
	 */
	public function setContentBlocks(array $contentBlocks): self
	{
		$this->contentBlocks = $contentBlocks;

		return $this;
	}
}
