<?php

namespace Bitrix\BIConnector\ExternalSource\FileReader\Csv;

class Settings
{
	public function __construct(
		readonly public string $path,
		readonly public string $delimiter,
		readonly public bool $hasHeaders,
		readonly public string $encoding,
	)
	{
	}
}
