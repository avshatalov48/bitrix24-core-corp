<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Command\Resource;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Command\CommandInterface;

class AddCommand implements CommandInterface
{
	private const MAX_COPIES = 50;

	private int|null $copies;

	public function __construct(
		public readonly int $createdBy,
		public readonly Entity\Resource\Resource $resource,
		int|null $copies = null,
	)
	{
		$this->copies = $copies && $copies > self::MAX_COPIES
			? self::MAX_COPIES
			: $copies
		;
	}

	public function getCopies(): int|null
	{
		return $this->copies;
	}

	public function toArray(): array
	{
		return [
			'resource' => $this->resource->toArray(),
			'createdBy' => $this->createdBy,
			'copies' => $this->copies,
		];
	}

	public static function mapFromArray(array $props): self
	{
		return new self(
			createdBy: $props['createdBy'],
			resource: Entity\Resource\Resource::mapFromArray($props['resource']),
			copies: $props['copies'] ?? null,
		);
	}
}
