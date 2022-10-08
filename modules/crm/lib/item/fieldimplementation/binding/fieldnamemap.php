<?php

namespace Bitrix\Crm\Item\FieldImplementation\Binding;

/**
 * @internal Is not covered by backwards compatibility
 */
final class FieldNameMap
{
	// like CONTACT_ID
	private ?string $singleId = null;
	// like CONTACT_IDS
	private ?string $multipleIds = null;
	// like CONTACT_BINDINGS
	private ?string $bindings = null;
	// like CONTACTS
	private ?string $boundEntities = null;

	public function getAllFilled(): array
	{
		return array_filter(
			[
				$this->singleId,
				$this->multipleIds,
				$this->bindings,
				$this->boundEntities,
			],
			fn($value): bool => !is_null($value),
		);
	}

	public function isSingleIdFilled(): bool
	{
		return !is_null($this->singleId);
	}

	public function getSingleId(): string
	{
		return $this->singleId;
	}

	public function setSingleId(string $singleId): self
	{
		$this->singleId = $singleId;
		return $this;
	}

	public function isMultipleIdsFilled(): bool
	{
		return !is_null($this->multipleIds);
	}

	public function getMultipleIds(): string
	{
		return $this->multipleIds;
	}

	public function setMultipleIds(string $multipleIds): self
	{
		$this->multipleIds = $multipleIds;
		return $this;
	}

	public function isBindingsFilled(): bool
	{
		return !is_null($this->bindings);
	}

	public function getBindings(): string
	{
		return $this->bindings;
	}

	public function setBindings(string $bindings): self
	{
		$this->bindings = $bindings;
		return $this;
	}

	public function isBoundEntitiesFilled(): bool
	{
		return !is_null($this->boundEntities);
	}

	public function getBoundEntities(): string
	{
		return $this->boundEntities;
	}

	public function setBoundEntities(string $boundEntities): self
	{
		$this->boundEntities = $boundEntities;
		return $this;
	}
}
