<?php

namespace Bitrix\Call\Analytics\Event;

class FollowUpEvent extends Event
{
	protected function setDefaultParams(): self
	{
		$this
			->setCallP5()
		;

		return $this;
	}

	protected function setCallP5(): self
	{
		if ($this->call->getId() !== null)
		{
			$this->p5 = 'callId_' . $this->call->getId();
		}

		return $this;
	}

	protected function getTool(): string
	{
		return 'im';
	}

	protected function getCategory(string $eventName): string
	{
		return 'call';
	}
}
