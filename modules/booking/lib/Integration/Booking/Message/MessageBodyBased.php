<?php

declare(strict_types=1);

namespace Bitrix\Booking\Integration\Booking\Message;

class MessageBodyBased extends Message
{
	private string $messageBody;

	public function setMessageBody(string $messageBody): self
	{
		$this->messageBody = $messageBody;

		return $this;
	}

	public function getMessageBody(): string
	{
		return $this->messageBody;
	}
}
