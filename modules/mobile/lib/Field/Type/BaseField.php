<?php

namespace Bitrix\Mobile\Field\Type;

abstract class BaseField
{
	public const TYPE = '';

	protected string $id;
	protected $value;
	protected string $title;
	protected array $data;
	protected array $userFieldInfo;
	protected bool $editable;
	protected bool $multiple;
	protected bool $required;

	/**
	 * @param string $id
	 * @param $value
	 */
	public function __construct(string $id, $value)
	{
		$this->id = $id;
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	abstract public function getFormattedValue();

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return BaseField
	 */
	public function setTitle(string $title): BaseField
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @param array $data
	 * @return BaseField
	 */
	public function setData(array $data): BaseField
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getUserFieldInfo(): array
	{
		return $this->userFieldInfo;
	}

	/**
	 * @param array $userFieldInfo
	 * @return BaseField
	 */
	public function setUserFieldInfo(array $userFieldInfo): BaseField
	{
		$this->userFieldInfo = $userFieldInfo;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEditable(): bool
	{
		return $this->editable;
	}

	/**
	 * @param bool $editable
	 * @return BaseField
	 */
	public function setEditable(bool $editable): BaseField
	{
		$this->editable = $editable;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * @param bool $multiple
	 * @return BaseField
	 */
	public function setMultiple(bool $multiple): BaseField
	{
		$this->multiple = $multiple;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * @param bool $required
	 * @return BaseField
	 */
	public function setRequired(bool $required): BaseField
	{
		$this->required = $required;
		return $this;
	}
}
