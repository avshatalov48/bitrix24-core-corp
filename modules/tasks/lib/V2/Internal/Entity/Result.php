<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Tasks\V2\Internal\Entity\Result\Status;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Result extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?int $taskId = null,
		public readonly ?string $text = null,
		public readonly ?User $author = null,
		public readonly ?int $createdAtTs = null,
		public readonly ?int $updatedAtTs = null,
		public readonly ?Status $status = null,
		public readonly ?array $fileIds = null,
	)
	{

	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function isOpen(): bool
	{
		return $this->status === Status::Open;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			taskId: static::mapInteger($props, 'taskId'),
			text: static::mapString($props, 'text'),
			author: static::mapEntity($props, 'author', User::class),
			createdAtTs: static::mapInteger($props, 'createdAtTs'),
			updatedAtTs: static::mapInteger($props, 'updatedAtTs'),
			status: static::mapBackedEnum($props, 'status', Status::class),
			fileIds: static::mapArray($props, 'fileIds'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'taskId' => $this->taskId,
			'text' => $this->text,
			'author' => $this->author?->toArray(),
			'createdAtTs' => $this->createdAtTs,
			'updatedAtTs' => $this->updatedAtTs,
			'status' => $this->status?->value,
			'fileIds' => $this->fileIds,
		];
	}
}
