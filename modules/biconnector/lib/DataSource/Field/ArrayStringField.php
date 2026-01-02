<?php

namespace Bitrix\BIConnector\DataSource\Field;

use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\DatasetField;

class ArrayStringField extends DatasetField
{
	protected const TYPE = 'array_string';

	protected bool $canSplitValues = false;

	public function __construct(string $code, ?string $name = null, ?Dataset $dataset = null)
	{
		parent::__construct($code, $name, $dataset);

		$this->isMultiple = true;
		$this->separator = ', ';
	}

	public function setSplitable(bool $isSplitable = true): static
	{
		$this->canSplitValues = $isSplitable;

		return $this;
	}

	/**
	 * This method has no effect. ArrayString is always multiple
	 *
	 * @param bool $multiple
	 * @return $this
	 */
	public function setMultiple(bool $multiple = true): static
	{
		return $this;
	}

	public function getFormatted(): array
	{
		$fields = parent::getFormatted();

		$fields['IS_VALUE_SPLITABLE'] = $this->canSplitValues;

		return $fields;
	}
}