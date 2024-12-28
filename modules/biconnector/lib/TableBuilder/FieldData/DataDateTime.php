<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

class DataDateTime extends Base
{
	public function getFormattedValue(): string
	{
		return $this->sqlHelper->convertToDbDateTime($this->value);
	}
}
