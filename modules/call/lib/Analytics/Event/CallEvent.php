<?php

namespace Bitrix\Call\Analytics\Event;

class CallEvent extends Event
{
	protected function setDefaultParams(): self
	{
		$this
			->setCallType()
			->setCallP1()
			->setCallP2()
			->setCallP3()
			->setCallP4()
			->setCallP5()
		;

		return $this;
	}

	protected function setCallType(): self
	{
		$this->type = match(true)
		{
			$this->call instanceof \Bitrix\Call\Call\BitrixCall => 'group',
			$this->call instanceof \Bitrix\Call\Call\PlainCall => 'private',
			$this->call instanceof \Bitrix\Call\Call\ConferenceCall => 'videoconf',
		};

		return $this;
	}

	protected function setCallP1(): self
	{
		$this->p1 = 'callLength_' . $this->call->getDuration();

		return $this;
	}

	protected function setCallP2(): self
	{
		$this->p2 = 'timeout_' . \Bitrix\Call\Call::ACTIVE_CALLS_DEPTH_HOURS * 3600;

		return $this;
	}

	protected function setCallP3(): self
	{
		$this->p3 = 'maxUserCount_' . count($this->call->getCallUsers());

		return $this;
	}

	protected function setCallP4(): self
	{
		$this->p4 = 'chatId_' . $this->call->getChatId();

		return $this;
	}

	protected function setCallP5(): self
	{
		$this->p5 = 'callId_' . $this->call->getUuid();

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
