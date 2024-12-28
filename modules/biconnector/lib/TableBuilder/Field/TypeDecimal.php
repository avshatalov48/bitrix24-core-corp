<?php

namespace Bitrix\BIConnector\TableBuilder\Field;

class TypeDecimal extends Base
{
	private const INTEGER = 22;
	private const DECIMAL = 6;

	public function getField(): string
	{
		return sprintf('`%s` DECIMAL(%d,%d)', $this->getName(), self::INTEGER, self::DECIMAL);
	}
}
