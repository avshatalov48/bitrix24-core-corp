<?php

namespace Bitrix\Location\Entity\Format;

use Bitrix\Location\Entity\Address\FieldType;
use Bitrix\Location\Entity\Generic\IField;

/**
 * Class Field
 * @package Bitrix\Location\Entity\Format
 * todo: validators
 */
final class Field implements IField
{
	/** @var int  */
	private $type = FieldType::UNKNOWN;
	/** @var int  */
	private $sort = 100;
	/** @var string  */
	private $name = '';
	/** @var string  */
	private $description = '';

	/**
	 * Field constructor.
	 * @param int $type
	 */
	public function __construct(int $type)
	{
		$this->type = $type;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getSort(): int
	{
		return $this->sort;
	}

	/**
	 * @param int $sort
	 * @return $this
	 */
	public function setSort(int $sort): self
	{
		$this->sort = (int)$sort;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName(string $name): self
	{
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * @return $this
	 */
	public function setDescription(string $description): self
	{
		$this->description = (string)$description;
		return $this;
	}
}
