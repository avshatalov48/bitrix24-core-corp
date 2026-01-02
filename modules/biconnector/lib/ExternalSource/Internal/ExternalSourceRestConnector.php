<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector\ExternalSource;

class ExternalSourceRestConnector extends EO_ExternalSourceRestConnector
{
	public function getCode(): string
	{
		$type = ExternalSource\Type::Rest->value;
		if (empty($this->getId()))
		{
			return "{$type}_0";
		}

		return "{$type}_{$this->getId()}";
	}
}
