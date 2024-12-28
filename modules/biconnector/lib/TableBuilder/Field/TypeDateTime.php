<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeDateTime extends Base
{
	public function getField(): string
	{
		return sprintf('`%s` DATETIME', $this->getName());
	}
}
