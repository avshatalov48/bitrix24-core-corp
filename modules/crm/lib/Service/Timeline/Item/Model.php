<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main\Type\Datetime;

class Model
{
	private bool $isScheduled = false;
	private bool $isFixed = false;

	private ?string $id = null;
	private ?int $authorId = null;
	private ?Datetime $date = null;
	private ?int $associatedEntityId = null;
	private ?int $associatedEntityTypeId = null;
	private ?int $typeCategoryId = null;
	private ?array $settings = [];
	private ?AssociatedEntityModel $associatedEntityModel = null;
	private ?HistoryItemModel $historyItemModel = null;


	/*
	 * @todo implement:
	 *
	 * ASSOCIATED_ENTITY_CLASS_NAME
	 * TYPE_ID
	 * TYPE_CATEGORY_ID
	 * COMMENT
	 */

	public static function createFromScheduledActivityArray(array $data): self
	{
		$deadline = ($data['DEADLINE'] && !\CCrmDateTimeHelper::IsMaxDatabaseDate($data['DEADLINE']))
			? DateTime::createFromUserTime($data['DEADLINE'])
			: null
		;

		return (new self())
			->setIsScheduled(true)
			->setId(self::getScheduledActivityModelId((int)$data['ID']))
			->setAssociatedEntityId((int)$data['ID'])
			->setAssociatedEntityTypeId(\CCrmOwnerType::Activity)
			->setAuthorId((int)$data['RESPONSIBLE_ID'])
			->setDate($deadline)
			->setAssociatedEntityModel(AssociatedEntityModel::createFromArray($data))
		;
	}

	public static function getScheduledActivityModelId(int $activityId): string
	{
		return \CCrmOwnerType::ActivityName . '_' . $activityId;
	}

	public static function createFromArray(array $data)
	{
		return (new self())
			->setId((string)$data['ID'])
			->setAssociatedEntityId((int)$data['ASSOCIATED_ENTITY_ID'])
			->setAssociatedEntityTypeId((int)$data['ASSOCIATED_ENTITY_TYPE_ID'])
			->setAuthorId((int)$data['AUTHOR_ID'])
			->setDate($data['CREATED'])
			->setSettings((array)$data['SETTINGS'])
			->setAssociatedEntityModel(self::createAssociatedEntityModel($data))
			->setHistoryItemModel(self::createHistoryItemModel($data))
			->setTypeCategoryId((int)$data['TYPE_CATEGORY_ID']);
		;
	}

	private static function createAssociatedEntityModel(array $data): AssociatedEntityModel
	{
		return AssociatedEntityModel::createFromArray((array)($data['ASSOCIATED_ENTITY'] ?? []));
	}

	private static function createHistoryItemModel(array $data): HistoryItemModel
	{
		$historyItemModelData = [];

		// all fields that do not belong to timelime tablet, intended to belong to history item model:
		$timelineEntity = TimelineTable::getEntity();
		foreach ($data as $fieldName => $fieldValue)
		{
			if (!$timelineEntity->hasField($fieldName))
			{
				$historyItemModelData[$fieldName] = $fieldValue;
			}
		}

		return HistoryItemModel::createFromArray($historyItemModelData);
	}

	public function isScheduled(): bool
	{
		return $this->isScheduled;
	}

	public function setIsScheduled(bool $isScheduled): self
	{
		$this->isScheduled = $isScheduled;

		return $this;
	}

	public function isFixed(): bool
	{
		return $this->isFixed;
	}

	public function setIsFixed(bool $isFixed): self
	{
		$this->isFixed = $isFixed;

		return $this;
	}

	public function getAssociatedEntityModel(): ?AssociatedEntityModel
	{
		return $this->associatedEntityModel;
	}

	public function setAssociatedEntityModel(?AssociatedEntityModel $associatedEntityModel): self
	{
		$this->associatedEntityModel = $associatedEntityModel;

		return $this;
	}

	public function getHistoryItemModel(): ?HistoryItemModel
	{
		return $this->historyItemModel;
	}

	public function setHistoryItemModel(?HistoryItemModel $historyItemModel): self
	{
		$this->historyItemModel = $historyItemModel;

		return $this;
	}

	public function getId(): ?string
	{
		return $this->id;
	}

	public function setId(?string $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getAuthorId(): ?int
	{
		return $this->authorId;
	}

	public function setAuthorId(?int $authorId): self
	{
		$this->authorId = $authorId;

		return $this;
	}

	public function getDate(): ?Datetime
	{
		return $this->date;
	}

	public function setDate(?Datetime $createdDate): self
	{
		$this->date = $createdDate;

		return $this;
	}

	public function getAssociatedEntityId(): ?int
	{
		return $this->associatedEntityId;
	}

	public function setAssociatedEntityId(?int $associatedEntityId): self
	{
		$this->associatedEntityId = $associatedEntityId;

		return $this;
	}

	public function getAssociatedEntityTypeId(): ?int
	{
		return $this->associatedEntityTypeId;
	}

	public function setAssociatedEntityTypeId(?int $associatedEntityTypeId): self
	{
		$this->associatedEntityTypeId = $associatedEntityTypeId;

		return $this;
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function setSettings(array $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	public function getTypeCategoryId(): ?int
	{
		return $this->typeCategoryId;
	}

	public function setTypeCategoryId(int $typeCategoryId): self
	{
		$this->typeCategoryId = $typeCategoryId;

		return $this;
	}
}
