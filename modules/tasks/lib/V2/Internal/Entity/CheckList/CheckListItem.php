<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\CheckList;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\AttachmentCollection;
use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class CheckListItem extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		public readonly ?string $nodeId = null,
		public readonly ?string $title = null,
		public readonly ?User $creator = null,
		public readonly ?User $toggledBy = null,
		public readonly ?DateTime $toggledDate = null,
		public readonly ?UserCollection $accomplices = null,
		public readonly ?UserCollection $auditors = null,
		public readonly ?AttachmentCollection $attachments = null,
		public readonly ?bool $isComplete = null,
		public readonly ?bool $isImportant = null,
		public readonly ?int $parentId = null,
		public readonly ?string $parentNodeId = null,
		public readonly ?int $sortIndex = null,
		public readonly ?array $actions = null,
		public readonly ?bool $collapsed = null,
		public readonly ?bool $expanded = null,
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
			nodeId: static::mapString($props, 'nodeId'),
			title: static::mapString($props, 'title'),
			creator: static::mapEntity($props, 'creator', User::class),
			toggledBy: static::mapEntity($props, 'toggledBy', User::class),
			toggledDate: static::mapDateTime($props, 'toggledDate'),
			accomplices: static::mapEntityCollection($props, 'accomplices', UserCollection::class),
			auditors: static::mapEntityCollection($props, 'auditors', UserCollection::class),
			attachments: static::mapEntityCollection($props, 'attachments', AttachmentCollection::class),
			isComplete: static::mapBool($props, 'isComplete'),
			isImportant: static::mapBool($props, 'isImportant'),
			parentId: static::mapInteger($props, 'parentId'),
			parentNodeId: static::mapString($props, 'parentNodeId'),
			sortIndex: static::mapInteger($props, 'sortIndex'),
			actions: static::mapArray($props, 'actions'),
			collapsed: static::mapBool($props, 'collapsed'),
			expanded: static::mapBool($props, 'expanded'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'nodeId' => $this->nodeId,
			'title' => $this->title,
			'creator' => $this->creator?->toArray(),
			'toggledBy' => $this->toggledBy?->toArray(),
			'toggledDate' => $this->toggledDate,
			'accomplices' => $this->accomplices?->toArray(),
			'auditors' => $this->auditors?->toArray(),
			'attachments' => $this->attachments?->toArray(),
			'isComplete' => $this->isComplete,
			'isImportant' => $this->isImportant,
			'parentId' => $this->parentId,
			'parentNodeId' => $this->parentNodeId,
			'sortIndex' => $this->sortIndex,
			'actions' => $this->actions,
			'collapsed' => $this->collapsed,
			'expanded' => $this->expanded,
		];
	}
}
