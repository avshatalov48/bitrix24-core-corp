<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract\SendableContract;

class EmailMessage implements SendableContract
{
	public function __construct(
		private string $eventName,
		private string $siteId,
		private array $templateParams,
		private ?int $messageId,
		private ?bool $isDuplicate = null,
		private string $userLang = LANGUAGE_ID,
	)
	{

	}

	public function sendImmediately(): void
	{
		\CEvent::SendImmediate(
			$this->eventName,
			$this->siteId,
			$this->templateParams,
			$this->isDuplicate,
			$this->messageId,
			[],
			$this->userLang,
		);
	}

	public function send(): void
	{
		\CEvent::Send(
			$this->eventName,
			$this->siteId,
			$this->templateParams,
			$this->isDuplicate,
			$this->messageId,
			[],
			$this->userLang
		);
	}
}
