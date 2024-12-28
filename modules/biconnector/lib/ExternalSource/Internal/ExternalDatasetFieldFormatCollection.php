<?php

namespace Bitrix\BIConnector\ExternalSource\Internal;

use Bitrix\BIConnector\ExternalSource\FieldType;

class ExternalDatasetFieldFormatCollection extends EO_ExternalDatasetFieldFormat_Collection
{
	public function getFormatByType(FieldType $type): ?string
	{
		foreach ($this as $setting)
		{
			if ($setting->getType() === $type->value)
			{
				return $setting->getFormat();
			}
		}

		return null;
	}
}
