<?php

namespace Bitrix\BIConnector\TableBuilder\FieldData;

class DataText extends Base
{
	public function getFormattedValue(): string
	{
		return $this->sqlHelper->convertToDbString($this->value);
	}
}
