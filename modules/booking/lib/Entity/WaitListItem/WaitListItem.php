<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\WaitListItem;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\EntityWithClientRelationInterface;
use Bitrix\Booking\Entity\EntityWithExternalDataRelationInterface;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;

class WaitListItem implements
	EntityInterface,
	EntityWithClientRelationInterface,
	EntityWithExternalDataRelationInterface
{
	private int|null $id = null;
	private int|null $createdBy = null;
	private int|null $createdAt = null;
	private int|null $updatedAt = null;
	private ClientCollection $clientCollection;
	private ExternalDataCollection $externalDataCollection;
	private string|null $note = null;
	private bool|null $isDeleted = null;

	public function __construct()
	{
		$this->clientCollection = new ClientCollection(...[]);
		$this->externalDataCollection = new ExternalDataCollection(...[]);
	}

	public function getId(): int|null
	{
		return $this->id;
	}

	public function setId(int|null $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getCreatedBy(): int|null
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int|null $createdBy): self
	{
		$this->createdBy = $createdBy;

		return $this;
	}

	public function getCreatedAt(): int|null
	{
		return $this->createdAt;
	}

	public function setCreatedAt(int|null $createdAt): self
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): int|null
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(int|null $updatedAt): self
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getClientCollection(): ClientCollection
	{
		return $this->clientCollection;
	}

	public function setClientCollection(ClientCollection $clientCollection): self
	{
		$this->clientCollection = $clientCollection;

		return $this;
	}

	public function getExternalDataCollection(): ExternalDataCollection
	{
		return $this->externalDataCollection;
	}

	public function setExternalDataCollection(ExternalDataCollection $externalDataCollection): self
	{
		$this->externalDataCollection = $externalDataCollection;

		return $this;
	}

	public function getNote(): string|null
	{
		return $this->note;
	}

	public function setNote(string|null $note): self
	{
		$this->note = $note;

		return $this;
	}

	public function isDeleted(): bool|null
	{
		return $this->isDeleted;
	}

	public function setDeleted(bool $deleted): self
	{
		$this->isDeleted = $deleted;

		return $this;
	}

	/**
	 * @return self
	 */
	public static function mapFromArray(array $props): EntityInterface
	{
		$waitListItem = new self();

		if (isset($props['clients']))
		{
			$waitListItem->setClientCollection(
				ClientCollection::mapFromArray((array)$props['clients'])
			);
		}

		if (isset($props['externalData']))
		{
			$waitListItem->setExternalDataCollection(
				ExternalDataCollection::mapFromArray((array)$props['externalData'])
			);
		}

		if (isset($props['isDeleted']))
		{
			$waitListItem->setDeleted((bool)$props['isDeleted']);
		}

		return $waitListItem
			->setId(isset($props['id']) ? (int)$props['id'] : null)
			->setCreatedBy(isset($props['createdBy']) ? (int)$props['createdBy'] : null)
			->setCreatedAt(isset($props['createdAt']) ? (int)$props['createdAt'] : null)
			->setUpdatedAt(isset($props['updatedAt']) ? (int)$props['updatedAt'] : null)
			->setNote(isset($props['note']) ? (string)$props['note'] : null)
		;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt,
			'updatedAt' => $this->updatedAt,
			'clients' => $this->clientCollection->toArray(),
			'externalData' => $this->externalDataCollection->toArray(),
			'note' => $this->note,
			'isDeleted' => $this->isDeleted,
		];
	}
}
