<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector;

class ExternalSource extends EO_ExternalSource
{
	/**
	 * Gets enum type
	 *
	 * @return BIConnector\ExternalSource\Type
	 */
	public function getEnumType(): BIConnector\ExternalSource\Type
	{
		return BIConnector\ExternalSource\Type::from($this->getType());
	}
}
