<?php

namespace Bitrix\Crm\Integration\Intranet\CustomSection;

class Page
{
	/** @var int|null */
	protected $id;
	/** @var int|null */
	protected $customSectionId;
	/** @var string|null */
	protected $code;
	/** @var string|null */
	protected $title;
	/** @var int|null */
	protected $sort;
	/** @var string|null */
	protected $settings;

	/**
	 * Returns ID of the page
	 *
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Sets ID of the page
	 *
	 * @param int $id
	 *
	 * @return Page
	 */
	public function setId(int $id): Page
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Returns ID of a custom section, which this page is associated with
	 *
	 * @return int|null
	 */
	public function getCustomSectionId(): ?int
	{
		return $this->customSectionId;
	}

	/**
	 * Sets ID of a custom section, which this page is associated with
	 *
	 * @param int $customSectionId
	 *
	 * @return Page
	 */
	public function setCustomSectionId(int $customSectionId): Page
	{
		$this->customSectionId = $customSectionId;

		return $this;
	}

	/**
	 * Returns CODE of this page
	 *
	 * @return string|null
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	/**
	 * Sets CODE of this page
	 *
	 * @param string $code
	 *
	 * @return Page
	 */
	public function setCode(string $code): Page
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * Returns TITLE of this page
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->title;
	}

	/**
	 * Sets TITLE of this page
	 *
	 * @param string $title
	 *
	 * @return Page
	 */
	public function setTitle(string $title): Page
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Returns SORT of this page
	 *
	 * @return int|null
	 */
	public function getSort(): ?int
	{
		return $this->sort;
	}

	/**
	 * Sets SORT of this page
	 *
	 * @param int $sort
	 *
	 * @return Page
	 */
	public function setSort(int $sort): Page
	{
		$this->sort = $sort;

		return $this;
	}

	/**
	 * Returns SETTINGS of this page
	 *
	 * @return string|null
	 */
	public function getSettings(): ?string
	{
		return $this->settings;
	}

	/**
	 * Sets SETTINGS of this page
	 *
	 * @param string $settings
	 *
	 * @return Page
	 */
	public function setSettings(string $settings): Page
	{
		$this->settings = $settings;

		return $this;
	}
}
