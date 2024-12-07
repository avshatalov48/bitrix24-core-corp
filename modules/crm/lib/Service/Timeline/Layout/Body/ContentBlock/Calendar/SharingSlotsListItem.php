<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Calendar;

use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class SharingSlotsListItem extends ContentBlock
{
	use CalendarSharing\FormatTrait;

	protected ?array $rule = null;

	public function getRendererName(): string
	{
		return 'SharingSlotsListItem';
	}

	public function getRule(): ?array
	{
		return $this->rule;
	}

	public function setRule(?array $rule): self
	{
		$this->rule = $rule;

		return $this;
	}

	protected function getProperties(): ?array
	{
		return [
			'rule' => $this->getRule(),
			'durationFormatted' => $this->getDurationFormatted(),
			'weekdaysFormatted' => $this->getWeekdaysFormatted(),
		];
	}

	protected function getDurationFormatted(): string
	{
		return $this->formatDuration($this->getRule()['slotSize']);
	}

	protected function getWeekdaysFormatted(): string
	{
		return $this->getRule()['weekdaysTitle'];
	}
}