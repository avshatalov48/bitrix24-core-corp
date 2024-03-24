<?php

namespace Bitrix\Intranet\CustomSection\DataStructures;

class CustomSectionPage
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
	protected $moduleId;
	/** @var string|null */
	protected $settings;
	/** @var string|null */
	protected $counterId;
	/** @var int|null */
	protected $counterValue;
	protected bool $isDisabledInCtrlPanel = false;

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
	 * @return $this
	 */
	public function setId(int $id): self
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
	 * @return $this
	 */
	public function setCustomSectionId(int $customSectionId): self
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
	 * @return $this
	 */
	public function setCode(string $code): self
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
	 * @return $this
	 */
	public function setTitle(string $title): self
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
	 * @return $this
	 */
	public function setSort(int $sort): self
	{
		$this->sort = $sort;

		return $this;
	}

	/**
	 * Returns MODULE_ID of this page
	 *
	 * @return string|null
	 */
	public function getModuleId(): ?string
	{
		return $this->moduleId;
	}

	/**
	 * Sets MODULE_ID of this page
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
	 * @return $this
	 */
	public function setSettings(string $settings): self
	{
		$this->settings = $settings;

		return $this;
	}

	/**
	 * Returns COUNTER_ID of this page
	 *
	 * @return string|null
	 */
	public function getCounterId(): ?string
	{
		return $this->counterId;
	}

	/**
	 * Sets COUNTER_ID of this page
	 *
	 * @param string $counterId
	 *
	 * @return $this
	 */
	public function setCounterId(string $counterId): self
	{
		$this->counterId = $counterId;

		return $this;
	}

	/**
	 * Returns COUNTER_VALUE of this page
	 *
	 * @return int|null
	 */
	public function getCounterValue(): ?int
	{
		return $this->counterValue;
	}

	/**
	 * Sets COUNTER_VALUE of this page
	 *
	 * @param int $counterValue
	 *
	 * @return $this
	 */
	public function setCounterValue(int $counterValue): self
	{
		$this->counterValue = $counterValue;

		return $this;
	}

	/**
	 * Returns IS_DISABLE of this page to be displayed in the Control Panel
	 *
	 * @return bool
	 */
	public function getDisabledInCtrlPanel(): bool
	{
		return $this->isDisabledInCtrlPanel;
	}

	/**
	 * Sets IS_DISABLE of this page to be displayed in the Control Panel
	 *
	 * @param bool $isDisabled
	 * @return $this
	 */
	public function setDisabledInCtrlPanel(bool $isDisabled): self
	{
		$this->isDisabledInCtrlPanel = $isDisabled;

		return $this;
	}
}
