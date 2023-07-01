<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class Audio extends ContentBlock
{
	protected int $id = 0;
	protected string $source = '';
	protected ?string $title = null;
	protected ?string $imageUrl = null;
	protected ?string $recordName = null;

	public function getRendererName(): string
	{
		return 'TimelineAudio';
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id = 0): self
	{
		$this->id = $id;

		return $this;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function setSource(string $source = ''): self
	{
		$this->source = $source;

		return $this;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setImageUrl(?string $imageUrl): self
	{
		$this->imageUrl = $imageUrl;

		return $this;
	}

	public function getImageUrl(): ?string
	{
		return $this->imageUrl;
	}

	public function setRecordName(?string $recordName): self
	{
		$this->recordName = $recordName;

		return $this;
	}

	public function getRecordName(): ?string
	{
		return $this->recordName;
	}

	protected function getProperties(): array
	{
		return [
			'id' => $this->getId(),
			'src' => $this->getSource(),
			'title' => $this->getTitle(),
			'imageUrl' => $this->getImageUrl(),
			'recordName' => $this->getRecordName(),
		];
	}
}
