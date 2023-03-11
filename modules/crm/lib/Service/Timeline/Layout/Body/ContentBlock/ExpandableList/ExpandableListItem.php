<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

final class ExpandableListItem extends ContentBlock
{
	private string $id;
	private ?string $title = null;
	private ?Action $titleAction = null;
	private ?string $image = null;
	private bool $showDummyImage = true;
	private bool $isSelected = false;
	private ?ContentBlock\LineOfTextBlocks $bottomBlock = null;
	private ?ExpandableListItemButton $button = null;

	public function __construct(string $id)
	{
		$this->id = $id;
	}

	public function getRendererName(): string
	{
		return 'ExpandableListItem';
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getTitleAction(): ?Action
	{
		return $this->titleAction;
	}

	public function setTitleAction(?Action $titleAction): self
	{
		$this->titleAction = $titleAction;

		return $this;
	}

	public function getImage(): ?string
	{
		return $this->image;
	}

	public function setImage(?string $image): self
	{
		$this->image = $image;

		return $this;
	}

	public function getShowDummyImage(): ?bool
	{
		return $this->showDummyImage;
	}

	public function setShowDummyImage(?bool $showDummyImage): self
	{
		$this->showDummyImage = $showDummyImage;

		return $this;
	}

	public function isSelected(): ?bool
	{
		return $this->isSelected;
	}

	public function setIsSelected(?bool $isSelected): self
	{
		$this->isSelected = $isSelected;

		return $this;
	}

	public function getBottomBlock(): ?ContentBlock\LineOfTextBlocks
	{
		return $this->bottomBlock;
	}

	public function setBottomBlock(?ContentBlock\LineOfTextBlocks $bottomBlock): self
	{
		$this->bottomBlock = $bottomBlock;

		return $this;
	}

	public function getButton(): ?ExpandableListItemButton
	{
		return $this->button;
	}

	public function setButton(?ExpandableListItemButton $button): self
	{
		$this->button = $button;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'title' => $this->getTitle(),
			'titleAction' => $this->getTitleAction(),
			'image' => $this->getImage(),
			'showDummyImage' => $this->getShowDummyImage(),
			'isSelected' => $this->isSelected(),
			'bottomBlock' => $this->getBottomBlock(),
			'button' => $this->getButton(),
		];
	}
}
