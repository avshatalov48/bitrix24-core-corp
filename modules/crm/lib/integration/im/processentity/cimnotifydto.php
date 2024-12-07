<?php

namespace Bitrix\Crm\Integration\Im\ProcessEntity;

class CIMNotifyDTO
{
	public ?string $messageType;
	public ?int $toUserId;
	public ?int $fromUserId;
	public ?int $notifyType;
	public ?string $notifyModule;
	public ?string $notifyEvent;
	public ?string $notifyTag;

	/** @var string|callable|null */
	protected mixed $notifyMessage;

	/** @var callable|string|null */
	protected mixed $notifyMessageOut;

	public function getMessageType(): ?string
	{
		return $this->messageType;
	}

	public function setMessageType(string $messageType): self
	{
		$this->messageType = $messageType;

		return $this;
	}

	public function getToUserId(): ?int
	{
		return $this->toUserId;
	}

	public function setToUserId(int $toUserId): self
	{
		$this->toUserId = $toUserId;

		return $this;
	}

	public function getFromUserId(): ?int
	{
		return $this->fromUserId;
	}

	public function setFromUserId(int $fromUserId): self
	{
		$this->fromUserId = $fromUserId;

		return $this;
	}

	public function getNotifyType(): ?int
	{
		return $this->notifyType;
	}

	public function setNotifyType(int $notifyType): self
	{
		$this->notifyType = $notifyType;

		return $this;
	}

	public function getNotifyModule(): ?string
	{
		return $this->notifyModule;
	}

	public function setNotifyModule(string $notifyModule): self
	{
		$this->notifyModule = $notifyModule;

		return $this;
	}

	public function getNotifyEvent(): ?string
	{
		return $this->notifyEvent;
	}

	public function setNotifyEvent(string $notifyEvent): self
	{
		$this->notifyEvent = $notifyEvent;

		return $this;
	}

	public function getNotifyTag(): ?string
	{
		return $this->notifyTag;
	}

	public function setNotifyTag(string $notifyTag): self
	{
		$this->notifyTag = $notifyTag;

		return $this;
	}

	public function getNotifyMessage(): callable|string|null
	{
		return $this->notifyMessage;
	}

	public function setNotifyMessage(
		callable|string|null $notifyMessage,
	): self
	{
		$this->notifyMessage = $notifyMessage;

		return $this;
	}

	public function getNotifyMessageOut(): callable|string|null
	{
		return $this->notifyMessageOut;
	}

	public function setNotifyMessageOut(
		callable|string|null $notifyMessageOut,
	): self
	{
		$this->notifyMessageOut = $notifyMessageOut;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'MESSAGE_TYPE' => $this->getMessageType(),
			'TO_USER_ID' => $this->getToUserId(),
			'FROM_USER_ID' => $this->getFromUserId(),
			'NOTIFY_TYPE' => $this->getNotifyType(),
			'NOTIFY_MODULE' => $this->getNotifyModule(),
			'NOTIFY_EVENT' => $this->getNotifyEvent(),
			'NOTIFY_TAG' => $this->getNotifyTag(),
			'NOTIFY_MESSAGE' => $this->getNotifyMessage(),
			'NOTIFY_MESSAGE_OUT' => $this->getNotifyMessageOut(),
		];
	}
}
