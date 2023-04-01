<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ValueChange extends ContentBlock
{
	protected ?ValueChangeItem $from = null;
	protected ?ValueChangeItem $to = null;

	public function getRendererName(): string
	{
		return 'ValueChange';
	}

	public function getFrom(): ?ValueChangeItem
	{
		return $this->from;
	}

	public function setFrom(?ValueChangeItem $from): self
	{
		$this->from = $from;

		return $this;
	}

	public function getTo(): ?ValueChangeItem
	{
		return $this->to;
	}

	public function setTo(?ValueChangeItem $to): self
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
