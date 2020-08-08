<?php

namespace Bitrix\Location\Entity\Address;

use Bitrix\Location\Entity\Location\Type;
use Bitrix\Location\Entity\Generic\IField;

/**
 * Class Field
 * @package Bitrix\Location\Entity\Address
 */
final class Field implements IField
{
	/** @var int  */
	private $type = FieldType::UNKNOWN;
	/** @var string  */
	private $value = '';

	public function __construct(int $type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setValue(string $value): self
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * @return int.
	 * @see Type
	 */
	public function getType(): int
	{
		return $this->type;
	}
}
