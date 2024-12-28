<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeText extends Base
{
	public function getField(): string
	{
		return sprintf('`%s` TEXT', $this->getName());
	}
}
