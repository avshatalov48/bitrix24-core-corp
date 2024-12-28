<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

class DataInt extends Base
{
	public function getFormattedValue(): mixed
	{
		return $this->sqlHelper->convertToDbInteger($this->value);
	}
}
