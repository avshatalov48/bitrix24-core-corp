<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\Main;
use Bitrix\BIConnector;

class CsvImporter extends Importer
{
	protected function getTableName(): string
	{
		return BIConnector\ExternalSource\Source\Csv::TABLE_NAME_PREFIX . $this->settings->tableName;
	}
}
