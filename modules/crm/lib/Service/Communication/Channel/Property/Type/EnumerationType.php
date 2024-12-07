<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Type;

class EnumerationType extends Base
{
	public function getPreparedValue(): array
	{
		return is_array($this->value) ? $this->value : [$this->value];
	}
}
