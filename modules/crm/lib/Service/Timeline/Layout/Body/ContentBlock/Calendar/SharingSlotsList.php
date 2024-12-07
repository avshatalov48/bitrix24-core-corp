<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Calendar;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

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

	protected function getProperties(): ?array
	{
		return [
			'listItems' => $this->getListItems(),
		];
	}
}