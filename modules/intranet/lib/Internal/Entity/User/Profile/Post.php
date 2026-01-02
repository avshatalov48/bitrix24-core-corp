<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Main\Entity\EntityInterface;

class Post implements EntityInterface
{
	public function __construct(
		public readonly int $id,
		public readonly string $text,
		/** @var int[] $fileIds */
		public readonly array $fileIds,
	)
	{}

	public function getId(): int
	{
		return $this->id;
	}
}
