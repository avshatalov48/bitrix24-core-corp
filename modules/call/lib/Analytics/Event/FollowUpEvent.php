<?php

namespace Bitrix\Call\Analytics\Event;

class FollowUpEvent extends Event
{
	protected function setDefaultParams(): self
	{
		if ($this->call->getId() !== null)
		{
			$this->setP5('callId_' . $this->call->getUuid());
		}

		return $this;
	}

	/**
	 * Parameter st[tool].
	 * @return string
	 */
	protected function getTool(): string
	{
		return 'im';
	}

	/**
	 * Parameter st[category].
	 * @param string $eventName
	 * @return string
	 */
	protected function getCategory(string $eventName): string
	{
		return 'call_followup';
	}
}
