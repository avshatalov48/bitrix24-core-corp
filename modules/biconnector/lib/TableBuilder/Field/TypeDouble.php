<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeDouble extends Base
{
	public function getField(): string
	{
		return sprintf('`%s` DOUBLE', $this->getName());
	}
}
