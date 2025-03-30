<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Message;

class MessageTemplateBased
{
	private string $templateCode;
	private array $placeholders;

	public function getTemplateCode(): string
	{
		return $this->templateCode;
	}

	public function getPlaceholders(): array
	{
		return $this->placeholders;
	}

	public function setTemplateCode(string $templateCode): self
	{
		$this->templateCode = $templateCode;

		return $this;
	}

	public function setPlaceholders(array $placeholders): self
	{
		$this->placeholders = $placeholders;

		return $this;
	}
}
