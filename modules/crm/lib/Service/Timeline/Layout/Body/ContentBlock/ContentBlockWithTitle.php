<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ContentBlockWithTitle extends ContentBlock
{
	protected ?string $title = null;
	protected ?bool $inline = null;
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
			'contentBlock' => $this->getContentBlock(),
		];
	}
}
