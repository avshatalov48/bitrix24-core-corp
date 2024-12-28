<?php

namespace Bitrix\BIConnector\ExternalSource\Importer;

use Bitrix\BIConnector;

class Field
{
	public function __construct(
		readonly string $externalCode,
		readonly string $name,
		readonly Biconnector\ExternalSource\FieldType $type,
		readonly ?string $format = null,
	)
	{
	}
}
