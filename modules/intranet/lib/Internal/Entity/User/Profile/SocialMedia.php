<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Internal\Enum\User\Profile\SocialMediaType;
use Bitrix\Main\Entity\EntityInterface;
use Bitrix\Main\Type\Contract\Arrayable;

class SocialMedia implements EntityInterface, Arrayable
{
	public function __construct(
		public readonly int $id,
		public readonly SocialMediaType $type,
		public readonly string $value,
		public readonly string $title,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'type' => $this->type->value,
			'title' => $this->title,
			'value' => $this->value,
		];
	}
}
