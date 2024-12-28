<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeInt extends Base
{
	public function getField(): string
	{
		return sprintf('`%s` BIGINT', $this->getName());
	}
}
