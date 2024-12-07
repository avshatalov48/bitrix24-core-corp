<?php

namespace Bitrix\Intranet\Entity;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\Main\Type\DateTime;

class Department
{
	public function __construct(
		private string $name,
		private ?int $id = null,
		private ?int $parentId = null,
		private ?int $createdBy = null,
		private ?DateTime $createdAt = null,
		private ?DateTime $updatedAt = null,
		private ?string $xmlId = null,
		private ?int $sort = 500,
		private ?bool $isActive = true,
		private ?bool $isGlobalActive = true,
		private ?int $depth = null,
		private ?string $accessCode = null,
	)
	{}

	public function getDepth(): ?int
	{
		return $this->depth;
	}

	public function setDepth(?int $depth): void
	{
		$this->depth = $depth;
	}

	public function getSort(): ?int
	{
		return $this->sort;
	}

	public function setSort(?int $sort): void
	{
		$this->sort = $sort;
	}

	public function isActive(): bool
	{
		return $this->isActive;
	}

	public function setIsActive(bool $isActive): void
	{
		$this->isActive = $isActive;
	}

	public function isGlobalActive(): bool
	{
		return $this->isGlobalActive;
	}

	public function setIsGlobalActive(bool $isGlobalActive): void
	{
		$this->isGlobalActive = $isActive;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getParentId(): ?int
	{
		return $this->parentId;
	}

	public function setParentId(?int $parentId): void
	{
		$this->parentId = $parentId;
	}

	public function getCreatedBy(): ?int
	{
		return $this->createdBy;
	}

	public function setCreatedBy(?int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getCreatedAt(): ?DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?DateTime $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	public function getUpdatedAt(): ?DateTime
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?DateTime $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

	public function getXmlId(): ?string
	{
		return $this->xmlId;
	}

	public function setXmlId(?string $xmlId): void
	{
		$this->xmlId = $xmlId;
	}

	/**
	 * @return string|null
	 */
	public function getAccessCode(): ?string
	{
		return $this->accessCode;
	}

	/**
	 * @param string|null $accessCode
	 */
	public function setAccessCode(?string $accessCode): void
	{
		$this->accessCode = $accessCode;
	}

	/**
	 * TODO: remove after remove UF_DEPARTMENT
	 * Only for migrated structures
	 * @return int|null
	 */
	public function getIblockSectionId(): ?int
	{
		return (new \Bitrix\Intranet\Service\IntranetOption)->get('humanresources_enabled') === 'Y'
			? DepartmentBackwardAccessCode::extractIdFromCode($this->accessCode)
			: $this->getId();
	}

	public function toIblockArray(): array
	{
		return [
			'ID' => (string)$this->getId(),
			'TIMESTAMP_X' => $this->getUpdatedAt()?->toString(),
			'MODIFIED_BY' => null,
			'DATE_CREATE' => $this->getCreatedAt()?->toString(),
			'CREATED_BY' => $this->getCreatedBy() > 0 ? (string)$this->getCreatedBy() : null,
			'IBLOCK_ID' => '3',
			'IBLOCK_SECTION_ID' => (string)$this->getParentId(),
			'ACTIVE' => $this->isActive() ? 'Y' : 'N',
			'GLOBAL_ACTIVE' => $this->isGlobalActive() ? 'Y' : 'N',
			'SORT' => $this->getSort() ? (string)$this->getSort() : null,
			'NAME' => $this->getName(),
			'PICTURE' => null,
			'DEPTH_LEVEL' => (string)$this->getDepth(),
			'DESCRIPTION' => null,
			'DESCRIPTION_TYPE' => 'text',
			'CODE' => null,
			'XML_ID' => $this->getXmlId(),
			'DETAIL_PICTURE' => null,
			'SOCNET_GROUP_ID' => null,
			'IBLOCK_TYPE_ID' => 'structure',
			'IBLOCK_CODE' => 'departments',
			'IBLOCK_EXTERNAL_ID' => 'departments',
			'EXTERNAL_ID' => $this->getXmlId(),
		];
	}
}