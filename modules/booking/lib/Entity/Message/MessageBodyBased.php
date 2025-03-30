<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Message;

class MessageBodyBased
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
