<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ClientMark extends ContentBlock
{
	public const POSITIVE = 'positive';
	public const NEUTRAL = 'neutral';
	public const NEGATIVE = 'negative';

	protected ?string $mark = null;
	protected ?string $text = null;

	public function getRendererName(): string
	{
		return 'ClientMark';
	}

	public function getMark(): ?string
	{
		return $this->mark;
	}

	public function setMark(?string $mark): self
	{
		$this->mark = $mark;

		return $this;
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function setText(?string $text): self
	{
		$this->text = $text;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'mark' => $this->getMark(),
			'text' => $this->getText(),
		];
	}
}
