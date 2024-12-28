<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector;

class ExternalDatasetField extends EO_ExternalDatasetField
{
	/**
	 * Gets enum type
	 *
	 * @return BIConnector\ExternalSource\FieldType
	 */
	public function getEnumType(): BIConnector\ExternalSource\FieldType
	{
		return BIConnector\ExternalSource\FieldType::from($this->getType());
	}
}
