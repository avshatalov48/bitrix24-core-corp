<?php

namespace Bitrix\Call\Analytics\Event;

use Bitrix\Call\Call\ConferenceCall;
use Bitrix\Call\Call\PlainCall;
use Bitrix\Im\Call\CallUser;

class FollowUpTaskEvent extends Event
{
	protected function setDefaultParams(): self
	{
		if ($this->call->getId() !== null)
		{
			$userCount = 0;
			foreach ($this->call->getCallUsers() as $user)
			{
				if ($user->getState() == CallUser::STATE_IDLE)
				{
					$userCount++;
				}
			}

			$this
				->setType($this->getCallType())//st[type]
				->setP3('userCount_'. $userCount)
				->setP4('recordDuration_'. $this->call->getDuration())
				->setP5('callId_' . $this->call->getUuid())
			;
		}

		return $this;
	}

	/**
	 * Parameter st[tool].
	 * @return string
	 */
	protected function getTool(): string
	{
		return 'ai';
	}

	/**
	 * Parameter st[category].
	 * @param string $eventName
	 * @return string
	 */
	protected function getCategory(string $eventName): string
	{
		return 'calls_operations';
	}

	/**
	 * @return string
	 */
	private function getCallType(): string
	{
		if ($this->call instanceof PlainCall)
		{
			return 'private';
		}
		if ($this->call instanceof ConferenceCall)
		{
			return 'videoconf';
		}

		return 'group';
	}
}
