<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field\Value;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client\Communication;
use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Client\Entity;
use Bitrix\Main\Type\Contract\Arrayable;
use JsonSerializable;

final class Client implements Arrayable, JsonSerializable
{
	public function __construct(
		public string $fullName,
		public ?string $openUrl = null,
		public ?Entity $entity = null,
		public array $communications = [],
	)
	{
	}

	public function toArray(): array
	{
		return [
			'fullName' => $this->fullName,
			'openUrl' => $this->openUrl,
			'communications' => array_map(static fn (Communication $communication) => $communication->toArray(), $this->communications),
			'entity' => $this->entity?->toArray(),
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
