<?php

namespace Bitrix\Intranet\Service;

class EmailMessage
{
	public function __construct(
		private string $eventName,
		private string $siteId,
		private array  $templateParams,
		private ?int   $messageId,
		private ?bool  $isDuplicate = null,
	)
	{

	}

	public function sendImmediately()
	{
		\CEvent::SendImmediate(
			$this->eventName,
			$this->siteId,
			$this->templateParams,
			$this->isDuplicate,
			$this->messageId
		);
	}

	public function send()
	{
		\CEvent::Send(
			$this->eventName,
			$this->siteId,
			$this->templateParams,
			$this->isDuplicate,
			$this->messageId
		);
	}
}