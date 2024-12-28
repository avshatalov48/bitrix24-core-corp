<?php

namespace Bitrix\BIConnector\DataSource\Field;

use Bitrix\BIConnector\DataSource\Dataset;
use Bitrix\BIConnector\DataSource\DatasetField;

class ArrayStringField extends DatasetField
{
	protected const TYPE = 'array_string';

	public function __construct(string $code, ?string $name = null, ?Dataset $dataset = null)
	{
		parent::__construct($code, $name, $dataset);

		$this->isMultiple = true;
		$this->separator = ', ';
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
}