<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin;
use Bitrix\Main\Type\Date;

class EditableDate extends ContentBlock
{
	use Mixin\Actionable;

	private ?Date $date = null;

	public function getRendererName(): string
	{
		return 'EditableDate';
	}

	public function getDate(): ?Date
	{
		return $this->date;
	}

	public function setDate(?Date $date): self
	{
		$this->date = $date;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'date' => $this->date,
			'action' => $this->getAction(),
		];
	}
}
