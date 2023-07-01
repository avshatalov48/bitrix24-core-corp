<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Calendar;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Type\DateTime;

class SharingSlotsListItem extends ContentBlock
{
	public const WORK_DAYS_TYPE = 'work_days';
	protected ?string $type = null;
	protected ?int $timeStart = null;

	protected ?int $timeEnd = null;
	protected ?int $slotLength = null;

	public function getRendererName(): string
	{
		return 'SharingSlotsListItem';
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(?string $type): self
	{
		$this->type = $type;
		return $this;
	}

	public function getTimeStart(): ?int
	{
		return $this->timeStart;
	}

	public function setTimeStart(int $timeStart): self
	{
		$this->timeStart = $timeStart;
		return $this;
	}

	public function getTimeEnd(): ?int
	{
		return $this->timeEnd;
	}

	public function setTimeEnd(?int $timeEnd): self
	{
		$this->timeEnd = $timeEnd;
		return $this;
	}

	public function getSlotLength(): ?int
	{
		return $this->slotLength;
	}

	public function setSlotLength(?int $slotLength): self
	{
		$this->slotLength = $slotLength;
		return $this;
	}

	protected function getProperties(): ?array
	{
		return [
			'type' => $this->getType(),
			'timeStart' => $this->getTimeStart(),
			'timeEnd' => $this->getTimeEnd(),
			'slotLength' => $this->getSlotLength(),
		];
	}
}