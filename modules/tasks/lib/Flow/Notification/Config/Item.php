<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\Flow\Notification\Presets;

class Item implements Arrayable
{
	public const STATUS_SYNC = 'SYNC';
	public const STATUS_ACTIVE = 'ACTIVE';
	public const STATUS_DELETED = 'DELETED';

	private When $when;
	private Where $where;
	private Caption $caption;
	private Message $message;
	/** @var Recipient[]  */
	private array $recipients;

	private int|null $id;
	private int|null $integrationId;

	public function __construct(
		Caption $caption,
		Message $message,
		When $when,
		Where $where,
		array $recipients,
		?int $id = null,
		?int $integrationId = null
	)
	{
		$this->id = $id;
		$this->when = $when;
		$this->where = $where;
		$this->caption = $caption;
		$this->message = $message;
		$this->recipients = $recipients;
		$this->integrationId = $integrationId;
	}

	public function getCaption(): Caption
	{
		return $this->caption;
	}

	public function getMessage(): Message
	{
		return $this->message;
	}

	public function getWhen(): When
	{
		return $this->when;
	}

	public function getWhere(): Where
	{
		return $this->where;
	}

	public function getChannel(): string
	{
		return $this->getWhere()->getValue();
	}

	public function getRecipients(): array
	{
		return $this->recipients;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getIntegrationId(): ?int
	{
		return $this->integrationId;
	}

	public function setIntegrationId(?int $integrationId): self
	{
		$this->integrationId = $integrationId;

		return $this;
	}

	public function isEqual(Item $item): bool
	{
		if ($this->getMessage()->getValue() !== $item->getMessage()->getValue())
		{
			return false;
		}

		if ($this->getWhen()->getValue() !== $item->getWhen()->getValue())
		{
			return false;
		}

		if ($this->getWhere()->getValue() !== $item->getWhere()->getValue())
		{
			return false;
		}

		if ($this->getRecipients() != $item->getRecipients())
		{
			return false;
		}

		return true;
	}

	public function getTranslatedMessage(): string
	{
		return $this->getMessage()->getTranslatedText();
	}

	public function toArray(): array
	{
		$recipients = [];

		foreach($this->recipients as $recipient)
		{
			$recipients[] = $recipient->getValue();
		}

		return [
			'caption' => [
				'key' => $this->caption->getValue(),
				'text' => $this->caption->getTranslatedText()
			],
			'when' => $this->when->getValue(),
			'where' => $this->where->getValue(),
			'recipients' => $recipients,
		];
	}

	public static function toObject(array $item): ?Item
	{
		if (!isset($item['caption']['key']))
		{
			throw new InvalidPayload();
		}

		$offset = (int)($item['when']['offset'] ?? 0);
		$caption = new Caption($item['caption']['key']);

		return (new Presets())->getItemByCaption($caption, $offset);
	}
}