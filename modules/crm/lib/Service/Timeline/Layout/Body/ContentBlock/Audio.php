<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class Audio extends ContentBlock
{
	protected int $id = 0;
	protected string $source = '';

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

	protected function getProperties(): array
	{
		return [
			'id' => $this->getId(),
			'src' => $this->getSource(),
		];
	}
}
