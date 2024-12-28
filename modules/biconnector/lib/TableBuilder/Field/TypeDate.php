<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeDate extends Base
{
	public function getField(): string
	{
		return sprintf('`%s` DATE', $this->getName());
	}
}
