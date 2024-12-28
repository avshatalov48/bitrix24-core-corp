<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\BIConnector\Collection;

class FieldCollection extends Collection
{
	public function getFieldByExternalCode($externalCode): ?Field
	{
		/** @var Field $field */
		foreach ($this->collection as $field)
		{
			if ($field->externalCode === $externalCode)
			{
				return $field;
			}
		}

		return null;
	}
}
