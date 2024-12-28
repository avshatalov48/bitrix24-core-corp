<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

class DataFloat extends Base
{
	public function getFormattedValue(): mixed
	{
		return $this->sqlHelper->convertToDbFloat($this->value);
	}
}
