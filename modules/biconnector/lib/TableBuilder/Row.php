<?php

namespace Bitrix\BIConnector\TableBuilder;

class Row
{
	private FieldDataCollection $fieldDataCollection;

	public function __construct(FieldDataCollection $fieldDataCollection)
	{
		$this->fieldDataCollection = $fieldDataCollection;
	}

	public function getRowValue(): string
	{
		$values = [];

		/** @var FieldData\Base $fieldData */
		foreach ($this->fieldDataCollection as $fieldData)
		{
			$values[] = $fieldData->getFormattedValue();
		}

		return sprintf('(%s)', implode(', ', $values));
	}
}
