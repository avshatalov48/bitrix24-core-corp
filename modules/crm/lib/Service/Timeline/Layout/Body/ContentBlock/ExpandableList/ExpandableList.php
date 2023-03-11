<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;

final class ExpandableList extends ContentBlock
{
	private ?string $title = null;
	private array $listItems = [];

	private bool $showMoreEnabled = true;
	private int $showMoreCnt = 3;
	private ?string $showMoreText = null;

	public function getRendererName(): string
	{
		return 'ExpandableList';
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @return ExpandableListItem[]
	 */
	public function getListItems(): array
	{
		return $this->listItems;
	}

	public function addListItem(ExpandableListItem $productListItem): self
	{
		$this->listItems[] = $productListItem;

		return $this;
	}

	public function isShowMoreEnabled(): bool
	{
		return $this->showMoreEnabled;
	}

	public function setShowMoreEnabled(bool $showMoreEnabled): self
	{
		$this->showMoreEnabled = $showMoreEnabled;

		return $this;
	}

	public function getShowMoreCnt(): int
	{
		return $this->showMoreCnt;
	}

	public function setShowMoreCnt(int $showMoreCnt): self
	{
		$this->showMoreCnt = $showMoreCnt;

		return $this;
	}

	public function getShowMoreText(): ?string
	{
		return $this->showMoreText;
	}

	public function setShowMoreText(?string $showMoreText): self
	{
		$this->showMoreText = $showMoreText;

		return $this;
	}

	protected function getProperties(): array
	{
		$showMoreText = $this->getShowMoreText();

		return [
			'title' => $this->title,
			'listItems' => $this->getListItems(),
			'showMoreEnabled' => $this->isShowMoreEnabled(),
			'showMoreCnt' => $this->getShowMoreCnt(),
			'showMoreText' => $showMoreText ?? Loc::getMessage('CRM_TIMELINE_CONTENT_BLOCK_EXPANDABLE_LIST_SHOW_ALL'),
		];
	}
}
