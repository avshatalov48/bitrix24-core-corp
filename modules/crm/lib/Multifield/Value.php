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
		/**
		 * Only props of the Value are compared. ValueExtra is not taken into account, since it contains
		 * only insignificant supporting data (namely COUNTRY_CODE).
		 * If this changes in the future and ValueExtra starts contain significant data, please add its comparison here.
		 * But make sure to handle edge cases (there is a pile of them)
		 */

		return (
			$this->typeId === $anotherValue->typeId
			&& $this->valueType === $anotherValue->valueType
			&& $this->value === $anotherValue->value
		);
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
		$valueToHash = clone $this;
		// id and valueExtra doesn't matter in equality
		$valueToHash->id = null;
		$valueToHash->valueExtra = null;

		return md5(serialize($valueToHash));
	}

	public function toArray(): array
	{
		return Assembler::arrayByValue($this);
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'typeId' => $this->getTypeId(),
			'valueType' => $this->getValueType(),
			'value' => $this->getValue(),
			'valueExtra' => $this->getValueExtra(),
		];
	}

	public function __clone(): void
	{
		if (is_object($this->valueExtra))
		{
			$this->valueExtra = clone $this->valueExtra;
		}
	}
}
