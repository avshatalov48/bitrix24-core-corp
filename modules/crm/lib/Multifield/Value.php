<?php

namespace Bitrix\Crm\Multifield;

use Bitrix\Main\Type\Contract\Arrayable;

final class Value implements Arrayable, \JsonSerializable
{
	/**
	 * @var int|null
	 */
	private ?int $id = null;

	/**
	 * @var string|null
	 */
	private ?string $typeId = null;

	/**
	 * @var string|null
	 */
	private ?string $valueType = null;

	/**
	 * @var string|null
	 */
	private ?string $value = null;

	/**
	 * @var ValueExtra|null
	 */
	private ?ValueExtra $valueExtra = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getTypeId(): ?string
	{
		return $this->typeId;
	}

	public function setTypeId(?string $typeId): self
	{
		$this->typeId = $typeId;

		return $this;
	}

	public function getValueType(): ?string
	{
		return $this->valueType;
	}

	public function setValueType(?string $valueType): self
	{
		$this->valueType = $valueType;

		return $this;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function setValue(?string $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getValueExtra(): ?ValueExtra
	{
		return $this->valueExtra;
	}

	public function setValueExtra(?ValueExtra $data): self
	{
		$this->valueExtra = $data;

		return $this;
	}

	public function isEqualTo(self $anotherValue): bool
	{
		return $this->getHash() === $anotherValue->getHash();
	}

	/**
	 * Returns string that represents this Value object. If two object have equal hashes, they are equal.
	 * Hash doesn't depend on an object instance, only properties are taken into account.
	 * Therefore, different instances with equal properties will have equal hashes.
	 * Calculation is one-way only, there is no way to recover object from its hash.
	 *
	 * @return string
	 */
	public function getHash(): string
	{
		return md5(serialize($this));
	}

	public function toArray(): array
	{
		return Assembler::arrayByValue($this);
	}

	public function jsonSerialize()
	{
		return [
			'id' => $this->getId(),
			'typeId' => $this->getTypeId(),
			'valueType' => $this->getValueType(),
			'value' => $this->getValue(),
			'valueExtra' => $this->getValueExtra(),
		];
	}
}
