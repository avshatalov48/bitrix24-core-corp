<?php

namespace Bitrix\Location\Entity;

use Bitrix\Location\Entity\Address\FieldType;
use Bitrix\Location\Entity\Format\Converter\ArrayConverter;
use Bitrix\Location\Entity\Format\FieldCollection;
use Bitrix\Main\Web\Json;

/**
 * Class Format
 * @package Bitrix\Location\Entity
 */
final class Format
{
	/** @var string  */
	private $name = '';
	/** @var string  */
	private $description = '';
	/** @var string  */
	private $code = '';
	/** @var string  */
	private $languageId;
	/** @var string Address view template */
	private $template = '';
	/** @var string Address components delimiter */
	private $delimiter = '';
	/** @var int Address field which will store unrecognized address information */
	private $fieldForUnRecognized = FieldType::UNKNOWN;
	/** @var FieldCollection */
	private $fieldCollection;

	/**
	 * Format constructor.
	 * @param string $languageId
	 */
	public function __construct(string $languageId)
	{
		$this->languageId = $languageId;
		$this->fieldCollection = new FieldCollection();
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
	public function setName(string $name): Format
	{
		$this->name = $name;
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
	public function setDescription(string $description): Format
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLanguageId(): string
	{
		return $this->languageId;
	}

	/**
	 * @param string $languageId
	 * @return $this
	 */
	public function setLanguageId(string $languageId): Format
	{
		$this->languageId = $languageId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode(string $code): Format
	{
		$this->code = $code;
		return $this;
	}

	/**
	 * @param FieldCollection $fieldCollection
	 * @return $this
	 * @internal
	 */
	public function setFieldCollection(FieldCollection $fieldCollection): self
	{
		$this->fieldCollection = $fieldCollection;
		return $this;
	}

	/**
	 * @return FieldCollection
	 * @internal
	 */
	public function getFieldCollection(): FieldCollection
	{
		return $this->fieldCollection;
	}

	/**
	 * Convert Format to JSON
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function toJson(): string
	{
		return Json::encode(ArrayConverter::convertToArray($this));
	}

	/**
	 * @return string
	 */
	public function getTemplate(): string
	{
		return $this->template;
	}

	/**
	 * @param string $template
	 * @return $this
	 */
	public function setTemplate(string $template): self
	{
		$this->template = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDelimiter(): string
	{
		return $this->delimiter;
	}

	/**
	 * @param string $delimiter
	 * @return $this
	 */
	public function setDelimiter(string $delimiter): self
	{
		$this->delimiter = $delimiter;
		return $this;
	}

	/**
	 * @param int $fieldType
	 * @return $this
	 */
	public function setFieldForUnRecognized(int $fieldType): self
	{
		$this->fieldForUnRecognized = $fieldType;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFieldForUnRecognized(): int
	{
		return $this->fieldForUnRecognized;
	}
}
