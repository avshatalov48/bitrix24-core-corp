<?php

namespace Bitrix\Crm\Counter\Monitor;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

class EntityChange
{
	private ItemIdentifier $identifier;

	private ?int $oldAssignedById;
	private ?int $newAssignedById;
	private ?string $oldStageId;
	private ?string $newStageId;
	private ?int $oldCategoryId;
	private ?int $newCategoryId;

	public static function create(
		int $entityTypeId,
		int $entityId,
		array $oldFields,
		array $newFields
	): ?self
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}
		if ($entityId <= 0)
		{
			return null;
		}

		$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);
		$stageIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
		$categoryIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CATEGORY_ID);

		$change = new self(
			new ItemIdentifier($entityTypeId, $entityId),
			isset($oldFields[$assignedByFiledName]) ? (int)$oldFields[$assignedByFiledName] : null,
			isset($newFields[$assignedByFiledName]) ? (int)$newFields[$assignedByFiledName] : null,
			isset($oldFields[$stageIdFieldName]) ? (string)$oldFields[$stageIdFieldName] : null,
			isset($newFields[$stageIdFieldName]) ? (string)$newFields[$stageIdFieldName] : null,
			isset($oldFields[$categoryIdFieldName]) ? (int)$oldFields[$categoryIdFieldName] : null,
			isset($newFields[$categoryIdFieldName]) ? (int)$newFields[$categoryIdFieldName] : null
		);

		return $change;
	}

	public function __construct(
		ItemIdentifier $identifier,
		?int $oldAssignedById,
		?int $newAssignedById,
		?string $oldStageId,
		?string $newStageId,
		?int $oldCategoryId,
		?int $newCategoryId
	)
	{
		$this->identifier = $identifier;
		$this->oldAssignedById = $oldAssignedById;
		$this->newAssignedById = $newAssignedById;
		$this->oldStageId = $oldStageId;
		$this->newStageId = $newStageId;
		$this->oldCategoryId = $oldCategoryId;
		$this->newCategoryId = $newCategoryId;
	}

	/**
	 * @return ItemIdentifier
	 */
	public function getIdentifier(): ItemIdentifier
	{
		return $this->identifier;
	}

	public function getOldAssignedById(): ?int
	{
		return $this->oldAssignedById;
	}

	public function getNewAssignedById(): ?int
	{
		return $this->newAssignedById;
	}

	public function getActualAssignedById(): ?int
	{
		return $this->newAssignedById ?? $this->oldAssignedById ?? null;
	}

	public function isAssignedByChanged(): bool
	{
		return $this->oldAssignedById !== $this->newAssignedById;
	}

	public function getOldStageId(): ?string
	{
		return $this->oldStageId;
	}

	public function getNewStageId(): ?string
	{
		return $this->newStageId;
	}

	public function isStageSemanticIdChanged(): bool
	{
		if (!is_null($this->oldStageId) && !is_null($this->newStageId))
		{
			$factory = Container::getInstance()->getFactory($this->identifier->getEntityTypeId());
			if (!$factory || !$factory->isStagesEnabled())
			{
				return false;
			}
			$oldStages = $factory->getStages($this->oldCategoryId);
			$oldStageSemanticId = null;
			foreach ($oldStages->getAll() as $stage)
			{
				if ($stage->getStatusId() === $this->oldStageId)
				{
					$oldStageSemanticId = $stage->getSemantics();
					break;
				}
			}

			$newStages = $factory->getStages($this->newCategoryId);
			$newStageSemanticId = null;
			foreach ($newStages->getAll() as $stage)
			{
				if ($stage->getStatusId() === $this->newStageId)
				{
					$newStageSemanticId = $stage->getSemantics();
					break;
				}
			}

			return $oldStageSemanticId !== $newStageSemanticId;
		}
		return $this->oldStageId !== $this->newStageId;
	}

	public function getOldCategoryId(): ?int
	{
		return $this->oldCategoryId;
	}

	public function getNewCategoryId(): ?int
	{
		return $this->newCategoryId;
	}

	public function getActualCategoryId(): ?int
	{
		return $this->newCategoryId ?? $this->oldCategoryId ?? null;
	}

	public function isCategoryIdChanged(): bool
	{
		return $this->oldCategoryId !== $this->newCategoryId;
	}

	public function wasEntityAddedOrDeleted(): bool
	{
		return $this->wasEntityAdded() || $this->wasEntityDeleted();
	}

	public function wasEntityAdded():bool
	{
		return (is_null($this->oldAssignedById) && is_null($this->oldStageId) && is_null($this->oldCategoryId));
	}

	public function wasEntityDeleted():bool
	{
		return (is_null($this->newAssignedById) && is_null($this->newStageId) && is_null($this->newCategoryId));
	}

	public function applyNewChange(self $entityChange): void
	{
		$this->newAssignedById = $entityChange->getNewAssignedById();
		$this->newStageId = $entityChange->getNewStageId();
		$this->newCategoryId = $entityChange->getNewCategoryId();
	}
}
