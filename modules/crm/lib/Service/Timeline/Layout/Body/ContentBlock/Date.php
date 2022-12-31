<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Type\DateTime;

class Date extends ContentBlock
{
	use TextPropertiesMixin;

	protected ?DateTime $date = null;
	private bool $withTime = true;

	public function isWithTime(): bool
	{
		return $this->withTime;
	}

	public function setWithTime(bool $withTime = true): self
	{
		$this->withTime = $withTime;

		return $this;
	}

	public function getRendererName(): string
	{
		return 'DateBlock';
	}

	public function getDate(): ?DateTime
	{
		return $this->date;
	}

	public function setDate(?DateTime $date): self
	{
		$this->date = $date;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			$this->getTextProperties(),
			[
				'value' => $this->getDate()->getTimestamp(),
				'withTime' => $this->isWithTime(),
			]
		);
	}
}
