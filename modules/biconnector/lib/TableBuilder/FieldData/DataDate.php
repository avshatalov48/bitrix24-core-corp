<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

class DataDate extends Base
{
	public function getFormattedValue(): string
	{
		return $this->sqlHelper->convertToDbDate($this->value);
	}
}
