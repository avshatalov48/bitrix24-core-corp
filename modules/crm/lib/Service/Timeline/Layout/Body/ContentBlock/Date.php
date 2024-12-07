<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Type\DateTime;

class Date extends ContentBlock implements TextPropertiesInterface
{
	use TextPropertiesMixin;

	protected ?DateTime $date = null;
	private bool $withTime = true;
	private ?string $format = null;
	private ?int $duration = null;

	public function isWithTime(): bool
	{
		return $this->withTime;
	}

	public function setWithTime(bool $withTime = true): self
	{
		$this->withTime = $withTime;

		return $this;
	}

	public function getFormat(): ?string
	{
		return $this->format;
	}

	public function setFormat(?string $format): Date
	{
		$this->format = $format;

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

	public function getDuration(): ?int
	{
		return $this->duration;
	}

	public function setDuration(?int $duration): self
	{
		$this->duration = $duration;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			$this->getTextProperties(),
			[
				'value' => $this->getDate()?->getTimestamp(),
				'withTime' => $this->isWithTime(),
				'format' => $this->getFormat(),
				'duration' => $this->getDuration(),
			]
		);
	}
}
