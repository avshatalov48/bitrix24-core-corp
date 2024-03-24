<?php

namespace Bitrix\Intranet\CustomSection\DataStructures;

class CustomSection
{
	/** @var int|null */
	protected $id;
	/** @var string|null */
	protected $code;
	/** @var string|null */
	protected $title;
	/** @var string|null */
	protected $moduleId;
	/** @var CustomSectionPage[] */
	protected $pages = [];

	/**
	 * Returns ID of this custom section
	 *
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Sets ID of this custom section
	 *
	 * @param int $id
	 *
	 * @return $this
	 */
	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Returns CODE of this custom section
	 *
	 * @return string|null
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	/**
	 * Sets CODE of this custom section
	 *
	 * @param string $code
	 *
	 * @return $this
	 */
	public function setCode(string $code): self
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * Returns TITLE of this custom section
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->title;
	}

	/**
	 * Sets TITLE of this custom section
	 *
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Returns MODULE_ID of this custom section
	 *
	 * @return string|null
	 */
	public function getModuleId(): ?string
	{
		return $this->moduleId;
	}

	/**
	 * Sets MODULE_ID of this custom section
	 *
	 * @param string $moduleId
	 *
	 * @return $this
	 */
	public function setModuleId(string $moduleId): self
	{
		$this->moduleId = $moduleId;

		return $this;
	}

	/**
	 * Returns pages of this custom section
	 *
	 * @return CustomSectionPage[]
	 */
	public function getPages(): array
	{
		return $this->pages;
	}

	/**
	 * Returns pages of this custom section
	 *
	 * @param CustomSectionPage[] $pages
	 *
	 * @return $this
	 */
	public function setPages(array $pages): self
	{
		$this->pages = $pages;

		return $this;
	}
}
