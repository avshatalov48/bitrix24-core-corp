<?php

namespace Bitrix\Tasks\Internals\Notification;

class Message
{
	private User $sender;
	private User $recepient;
	private Metadata $metadata;

	public function __construct(
		User $sender,
		User $recepient,
		Metadata $metadata
	)
	{
		$this->sender = $sender;
		$this->recepient = $recepient;
		$this->metadata = $metadata;
	}

	public function getSender(): User
	{
		return $this->sender;
	}

	public function getRecepient(): User
	{
		return $this->recepient;
	}

	public function getMetaData(): Metadata
	{
		return $this->metadata;
	}

	public function addMetaData(string $key, mixed $value): void
	{
		$this->metadata->addParams($key, $value);
	}
}