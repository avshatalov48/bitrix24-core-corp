<?php

namespace Bitrix\Crm\Item;

/**
 * Class DisabledField
 *
 * @package Bitrix\Crm\Item
 */
final class DisabledField
{
	/** @var string */
	private string $name;

	/** @var bool */
	private bool $isCategoryDependent = false;

	/**
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param bool $isCategoryDependent
	 * @return DisabledField
	 */
	public function setIsCategoryDependent(bool $isCategoryDependent): DisabledField
	{
		$this->isCategoryDependent = $isCategoryDependent;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isCategoryDependent(): bool
	{
		return $this->isCategoryDependent;
	}
}
