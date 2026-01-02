<?php

namespace Bitrix\BIConnector\ExternalSource\Exporter;

use Bitrix\BIConnector\ExternalSource\Internal\ExternalDataset;

class Settings
{
	public function __construct(
		readonly public ExternalDataset $dataset,
		readonly public Writer $writer,
		readonly public DataProvider $dataProvider,
	)
	{
	}
}
