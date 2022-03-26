<?php

namespace Bitrix\Mobile\Push;

use Bitrix\Main\Security\Random;

/**
 * Data-class represents push-notification from backend to mobile app.
 */
class Message implements \JsonSerializable
{
	/** @var string */
	protected $id;

	/** @var string */
	protected $type;

	/** @var string */
	protected $title;

	/** @var string */
	protected $body;

	/** @var array */
	protected $payload;

	public function __construct(string $type, string $title = '', string $body = '', array $payload = [])
	{
		$this->id = Random::getString(32);
		$this->type = $type;
		$this->title = $title;
		$this->body = $body;
		$this->payload = $payload;
	}

	/**
	 * Static constructor for silent push-messages with payload only.
	 */
	public static function createWithPayload(string $type, array $payload): Message
	{
		return new self($type, '', '', $payload);
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	public function getPayload(): array
	{
		return $this->payload;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'title' => $this->title,
			'body' => $this->body,
			'payload' => $this->payload,
		];
	}
}
