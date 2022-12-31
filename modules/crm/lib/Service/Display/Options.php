<?php

namespace Bitrix\Crm\Service\Display;


class Options
{
	private $fileEntityTypeId;
	private $fileUrlTemplate;
	private $gridId;
	private $multipleFieldsDelimiter = ', ';
	private $restrictedItemIds = [];
	private $restrictedFieldsToShow = [];
	private $restrictedValueTextReplacer;
	private $restrictedValueHtmlReplacer;
	private $showOnlyText = false;

	public static function createFromArray(array $options)
	{
		$instance = new self();
		if (array_key_exists('FILE_ENTITY_TYPE_ID', $options))
		{
			$instance->setFileEntityTypeId((int)$options['FILE_ENTITY_TYPE_ID']);
		}

		if (array_key_exists('FILE_URL_TEMPLATE', $options))
		{
			$instance->setFileUrlTemplate((string)$options['FILE_URL_TEMPLATE']);
		}

		if (array_key_exists('GRID_ID', $options))
		{
			$instance->setGridId((string)$options['GRID_ID']);
		}

		return $instance;
	}

	public function getFileEntityTypeId(): ?int
	{
		return $this->fileEntityTypeId;
	}

	public function setFileEntityTypeId(?int $fileEntityTypeId): Options
	{
		$this->fileEntityTypeId = $fileEntityTypeId;

		return $this;
	}

	public function getGridId(): ?string
	{
		return $this->gridId;
	}

	public function setGridId(?string $gridId): Options
	{
		$this->gridId = $gridId;

		return $this;
	}

	public function getFileUrlTemplate(): ?string
	{
		return $this->fileUrlTemplate;
	}

	public function setFileUrlTemplate(?string $fileUrlTemplate): Options
	{
		$this->fileUrlTemplate = $fileUrlTemplate;

		return $this;
	}

	public function getMultipleFieldsDelimiter(): string
	{
		return $this->multipleFieldsDelimiter;
	}

	public function setMultipleFieldsDelimiter(string $delimiter): Options
	{
		$this->multipleFieldsDelimiter = $delimiter;

		return $this;
	}

	public function getRestrictedItemIds(): array
	{
		return $this->restrictedItemIds;
	}

	public function setRestrictedItemIds(array $restrictedItemIds): self
	{
		$this->restrictedItemIds = $restrictedItemIds;

		return $this;
	}

	public function getRestrictedFieldsToShow(): array
	{
		return $this->restrictedFieldsToShow;
	}

	public function setRestrictedFieldsToShow(array $restrictedFieldsToShow): self
	{
		$this->restrictedFieldsToShow = $restrictedFieldsToShow;

		return $this;
	}

	public function getRestrictedValueTextReplacer(): ?string
	{
		return $this->restrictedValueTextReplacer;
	}

	public function setRestrictedValueTextReplacer(string $restrictedValueTextReplacer): self
	{
		$this->restrictedValueTextReplacer = $restrictedValueTextReplacer;

		return $this;
	}

	public function getRestrictedValueHtmlReplacer(): ?string
	{
		return $this->restrictedValueHtmlReplacer;
	}

	public function setRestrictedValueHtmlReplacer(string $restrictedValueHtmlReplacer): self
	{
		$this->restrictedValueHtmlReplacer = $restrictedValueHtmlReplacer;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isShowOnlyText(): bool
	{
		return $this->showOnlyText;
	}

	/**
	 * @param bool $showOnlyText
	 * @return Options
	 */
	public function setShowOnlyText(bool $showOnlyText): Options
	{
		$this->showOnlyText = $showOnlyText;
		return $this;
	}
}
