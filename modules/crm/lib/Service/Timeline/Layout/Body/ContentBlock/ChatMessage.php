<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ChatMessage extends ContentBlock
{
	protected ?string $message = null;
	protected bool $isIncoming = false;

	public function getRendererName(): string
	{
		return 'ChatMessage';
	}

	public function isIncoming(): bool
	{
		return $this->isIncoming;
	}

	public function setIsIncoming(bool $isIncoming): self
	{
		$this->isIncoming = $isIncoming;

		return $this;
	}

	public function getMessage(): ?string
	{
		return $this->message;
	}

	public function setMessage(?string $message): self
	{
		$this->message = $message;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'messageHtml' => $this->getMessage(),
			'messageText' => HTMLToTxt($this->getMessage()), // for mobile
			'isIncoming' => $this->isIncoming(),
		];
	}
}
