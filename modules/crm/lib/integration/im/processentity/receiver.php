<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity;

class Receiver
{
	public function __construct(
		protected int $id,
		protected ?string $messageType,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getMessageType(): ?string
	{
		return $this->messageType;
	}

	public function setMessageType(?string $messageType): void
	{
		$this->messageType = $messageType;
	}
}
