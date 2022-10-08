<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ValueChange extends ContentBlock
{
	protected ?string $from = null;
	protected ?string $to = null;

	public function getRendererName(): string
	{
		return 'ValueChange';
	}

	public function getFrom(): ?string
	{
		return $this->from;
	}

	public function setFrom(?string $from): self
	{
		$this->from = $from;

		return $this;
	}

	public function getTo(): ?string
	{
		return $this->to;
	}

	public function setTo(?string $to): self
	{
		$this->to = $to;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'from' => $this->getFrom(),
			'to' => $this->getTo(),
		];
	}
}
