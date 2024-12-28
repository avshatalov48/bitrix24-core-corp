<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\BIConnector;

class Settings
{
	public function __construct(
		readonly public string $tableName,
		readonly public BIConnector\ExternalSource\FileReader\Base $reader,
		readonly public FieldCollection $fieldCollection
	)
	{
	}
}
