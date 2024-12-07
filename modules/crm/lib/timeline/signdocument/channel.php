<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Type\Contract\Arrayable;

class Channel implements \JsonSerializable, Arrayable
{
	public const TYPE_SMS = 'sms';
	public const TYPE_WHATSAPP = 'whatsapp';
	public const TYPE_EMAIL = 'email';
	public const TYPE_CHAT = 'im';
	public const TYPE_B24 = 'b24';

	protected ?string $type = null;
	protected ?string $identifier = null;

	protected function __construct(string $type, string $identifier)
	{
		$this->validateType($type);
		$this->type = $type;
		$this->identifier = $identifier;
	}

	public static function createFromArray(array $data): self
	{
		return new Channel($data['type'], $data['identifier']);
	}

	private function validateType(string $type): void
	{
		if (
			$type !== static::TYPE_SMS
			&& $type !== static::TYPE_WHATSAPP
			&& $type !== static::TYPE_EMAIL
			&& $type !== static::TYPE_CHAT
			&& $type !== static::TYPE_B24
		)
		{
			throw new ArgumentOutOfRangeException('type');
		}
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->type,
			'identifier' => $this->identifier,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
