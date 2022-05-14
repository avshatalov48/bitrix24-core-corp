<?php

namespace Bitrix\Crm\Integrity\CriterionRegistrar;

final class Data
{
	/** @var int */
	private $entityTypeId;
	/** @var int */
	private $entityId;
	/** @var array */
	private $previousFields = [];
	/** @var array */
	private $currentFields = [];

	public function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	public function setEntityTypeId(int $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function getEntityId(): ?int
	{
		return $this->entityId;
	}

	public function setEntityId(int $entityId): self
	{
		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * Returns field values that represent the previous state of a source. For example, values before update
	 *
	 * @return Array<string, mixed>
	 */
	public function getPreviousFields(): array
	{
		return $this->previousFields;
	}

	/**
	 * Sets field values that represent the previous state of a source. For example, values before update
	 * Provide them if fields values were changed from the moment of last registration
	 *
	 * @param Array<string, mixed> $previousFields
	 * @return $this
	 */
	public function setPreviousFields(array $previousFields): self
	{
		$this->previousFields = $previousFields;

		return $this;
	}

	/**
	 * Returns field values that represent the current state of a source
	 *
	 * @return Array<string, mixed>
	 */
	public function getCurrentFields(): array
	{
		return $this->currentFields;
	}

	/**
	 * Sets field values that represent the current state of a source
	 *
	 * @param Array<string, mixed> $currentFields
	 * @return $this
	 */
	public function setCurrentFields(array $currentFields): self
	{
		$this->currentFields = $currentFields;

		return $this;
	}
}
