<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ContentBlockWithTitle extends ContentBlock
{
	protected ?string $title = null;
	protected ?bool $inline = null;
	protected ?bool $wordWrap = null;
    protected bool $fixedWidth = true;
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

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

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
			'inline' => $this->getInline(),
			'wordWrap' => $this->getWordWrap(),
            'fixedWidth' => $this->getFixedWidth(),
			'contentBlock' => $this->getContentBlock(),
		];
	}
}
