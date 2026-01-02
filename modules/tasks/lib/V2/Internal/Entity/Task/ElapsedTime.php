<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Task\Elapsed\Source;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class ElapsedTime extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public ?int $id = null,
		public ?int $userId = null,
		public ?int $taskId = null,
		public ?int $minutes = null,
		public ?int $seconds = null,
		public ?Source $source = null,
		public ?string $text = null,
		public ?int $createdAtTs = null,
		public ?int $startTs = null,
		public ?int $stopTs = null,
	)
	{

	}
	public function getId(): ?int
	{
		return $this->id;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			userId: static::mapInteger($props, 'userId'),
			taskId: static::mapInteger($props, 'taskId'),
			minutes: static::mapInteger($props, 'minutes'),
			seconds: static::mapInteger($props, 'seconds'),
			source: static::mapBackedEnum($props, 'source', Source::class),
			text: static::mapString($props, 'text'),
			createdAtTs: static::mapInteger($props, 'createdAtTs'),
			startTs: static::mapInteger($props, 'startTs'),
			stopTs: static::mapInteger($props, 'stopTs'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'taskId' => $this->taskId,
			'minutes' => $this->minutes,
			'seconds' => $this->seconds,
			'source' => $this->source?->value,
			'text' => $this->text,
			'createdAtTs' => $this->createdAtTs,
			'startTs' => $this->startTs,
			'stopTs' => $this->stopTs,
		];
	}
}
