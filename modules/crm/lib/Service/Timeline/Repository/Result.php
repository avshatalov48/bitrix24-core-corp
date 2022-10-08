<?php

namespace Bitrix\Crm\Service\Timeline\Repository;

use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Main\Type\DateTime;

class Result extends \Bitrix\Main\Result
{
	protected array $items = [];
	protected int $offsetId = 0;
	protected ?DateTime $offsetTime = null;

	/**
	 * @return Item[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @param Item[] $items
	 * @return $this
	 */
	public function setItems(array $items): self
	{
		$this->items = $items;

		return $this;
	}

	public function getOffsetId(): int
	{
		return $this->offsetId;
	}

	public function setOffsetId(int $offsetId): self
	{
		$this->offsetId = $offsetId;

		return $this;
	}

	public function getOffsetTime(): ?DateTime
	{
		return $this->offsetTime;
	}

	public function setOffsetTime(?DateTime $offsetTime): self
	{
		$this->offsetTime = $offsetTime;

		return $this;
	}
}
