<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Calendar;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Type\DateTime;

class SharingSlotsList extends ContentBlock
{
	private array $listItems = [];
	protected bool $isEditable = false;

	public function getRendererName(): string
	{
		return 'SharingSlotsList';
	}

	/**
	 * @return SharingSlotsListItem[]
	 */
	public function getListItems(): array
	{
		return $this->listItems;
	}

	public function addListItem(SharingSlotsListItem $sharingSlotsListItem): self
	{
		$this->listItems[] = $sharingSlotsListItem;

		return $this;
	}

	public function getIsEditable(): bool
	{
		return $this->isEditable;
	}

	public function setIsEditable(bool $isEditable = true): self
	{
		$this->isEditable = $isEditable;
		return $this;
	}

	protected function getProperties(): ?array
	{
		return [
			'listItems' => $this->getListItems(),
			'isEditable' => $this->getIsEditable(),
		];
	}
}