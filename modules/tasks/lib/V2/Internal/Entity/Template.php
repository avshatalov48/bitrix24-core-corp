<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Tasks\V2\Internal\Entity\Task\Priority;
use Bitrix\Tasks\V2\Internal\Entity\Template\ReplicateParams;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Template extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?Task $task = null,
		#[NotEmpty]
		public readonly ?string $title = null,
		public readonly ?string $description = null,
		public readonly ?User $creator = null,
		#[NotEmpty]
		public readonly ?UserCollection $responsibleCollection = null,
		public readonly ?int $deadlineAfterTs = null,
		public readonly ?int $startDatePlanTs = null,
		public readonly ?int $endDatePlanTs = null,
		public readonly ?bool $replicate = null,
		public readonly ?array $fileIds = null,
		public readonly ?array $checklist = null,
		public readonly ?Group $group = null,
		public readonly ?Priority $priority = null,
		public readonly ?UserCollection $accomplices = null,
		public readonly ?UserCollection $auditors = null,
		public readonly ?self $parent = null,
		public readonly ?ReplicateParams $replicateParams = null,
	)
	{

	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'task' => $this->task?->toArray(),
			'title' => $this->title,
			'description' => $this->description,
			'creator' => $this->creator?->toArray(),
			'responsibleCollection' => $this->responsibleCollection?->toArray(),
			'deadlineAfterTs' => $this->deadlineAfterTs,
			'startDatePlanTs' => $this->startDatePlanTs,
			'endDatePlanTs' => $this->endDatePlanTs,
			'replicate' => $this->replicate,
			'fileIds' => $this->fileIds,
			'checklist' => $this->checklist,
			'group' => $this->group?->toArray(),
			'priority' => $this->priority?->value,
			'accomplices' => $this->accomplices?->toArray(),
			'auditors' => $this->auditors?->toArray(),
			'parent' => $this->parent?->toArray(),
			'replicateParams' => $this->replicateParams?->toArray(),
		];
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			task: static::mapEntity($props, 'task', Task::class),
			title: static::mapString($props, 'title'),
			description: static::mapString($props, 'description'),
			creator: static::mapEntity($props, 'creator', User::class),
			responsibleCollection: static::mapEntityCollection($props, 'responsibleCollection', UserCollection::class),
			deadlineAfterTs: static::mapInteger($props, 'deadlineAfterTs'),
			startDatePlanTs: static::mapInteger($props, 'startDatePlanTs'),
			endDatePlanTs: static::mapInteger($props, 'endDatePlanTs'),
			replicate: static::mapBool($props, 'replicate'),
			fileIds: static::mapArray($props, 'fileIds'),
			checklist: static::mapArray($props, 'checklist'),
			group: static::mapEntity($props, 'group', Group::class),
			priority: static::mapBackedEnum($props, 'priority', Priority::class),
			accomplices: static::mapEntityCollection($props, 'accomplices', UserCollection::class),
			auditors: static::mapEntityCollection($props, 'auditors', UserCollection::class),
			parent: static::mapEntity($props, 'parent', self::class),
			replicateParams: static::mapValueObject($props, 'replicateParams', ReplicateParams::class),
		);
	}
}
