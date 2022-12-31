<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Main\Result;

/**
 * Class ConversionResult
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class ConversionResult extends Result
{
	private ?int $entityTypeId = null;

	private ?int $entityId = null;

	/** @var string[] */
	private array $warnings = [];

	/**
	 * @return int|null
	 */
	public function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	/**
	 * @param int|null $entityTypeId
	 * @return ConversionResult
	 */
	public function setEntityTypeId(?int $entityTypeId): ConversionResult
	{
		$this->entityTypeId = $entityTypeId;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getEntityId(): ?int
	{
		return $this->entityId;
	}

	/**
	 * @param int|null $entityId
	 * @return ConversionResult
	 */
	public function setEntityId(?int $entityId): ConversionResult
	{
		$this->entityId = $entityId;
		return $this;
	}

	/**
	 * @param string[] $warnings
	 * @return $this
	 */
	public function addWarnings(array $warnings): ConversionResult
	{
		foreach ($warnings as $warning)
		{
			$this->warnings[] = $warning;
		}

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}
}
