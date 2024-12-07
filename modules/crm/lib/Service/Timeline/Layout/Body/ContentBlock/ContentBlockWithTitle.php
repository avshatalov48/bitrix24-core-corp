<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ContentBlockWithTitle extends ContentBlock
{
	public const ALIGN_CENTER = 'center';
	public const ALIGN_START = 'flex-start';
	public const ALIGN_END = 'flex-end';
	public const ALIGN_STRETCH = 'stretch';
	public const ALIGN_BASELINE = 'baseline';

	protected ?string $title = null;
	protected ?int $titleBottomPadding = null;
	protected ?bool $inline = null;
	protected ?bool $wordWrap = null;
    protected bool $fixedWidth = true;
	protected ?string $alignItems = null;
	protected ?ContentBlock $contentBlock = null;

	public function getRendererName(): string
	{
		return 'WithTitle';
	}

	public function getInline(): ?bool
	{
		return $this->inline;
	}

	public function setInline(?bool $inline = true): self
	{
		$this->inline = $inline;

		return $this;
	}

	public function getWordWrap(): ?bool
	{
		return $this->wordWrap;
	}

	public function setWordWrap(?bool $wordWrap = true): self
	{
		$this->wordWrap = $wordWrap;

		return $this;
	}

    public function getFixedWidth(): bool
    {
        return $this->fixedWidth;
    }

    public function setFixedWidth(bool $fixedWidth): self
    {
        $this->fixedWidth = $fixedWidth;

        return $this;
    }

	public function getAlignItems(): ?string
	{
		return $this->alignItems;
	}

	public function setAlignItems(string $alignItems): self
	{
		$this->alignItems = $alignItems;

		return $this;
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

	public function getTitleBottomPadding(): ?int
	{
		return $this->titleBottomPadding;
	}

	public function setTitleBottomPadding(?int $titleBottomPadding): self
	{
		$this->titleBottomPadding = $titleBottomPadding;

		return $this;
	}

	public function getContentBlock(): ?ContentBlock
	{
		return $this->contentBlock;
	}

	public function setContentBlock(?ContentBlock $contentBlock): self
	{
		$this->contentBlock = $contentBlock;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'title' => $this->getTitle(),
			'titleBottomPadding' => $this->getTitleBottomPadding(),
			'inline' => $this->getInline(),
			'wordWrap' => $this->getWordWrap(),
            'fixedWidth' => $this->getFixedWidth(),
			'alignItems' => $this->getAlignItems(),
			'contentBlock' => $this->getContentBlock(),
		];
	}
}
