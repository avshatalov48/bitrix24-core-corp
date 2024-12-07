<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Type;

class StringType extends Base
{
	public function getPreparedValue(): array
	{
		return $this->value;
	}
}
