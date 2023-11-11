<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion;

use Bitrix\Tasks\Replicator\Template\Repository;

interface Converter
{
	public function convert(Repository $repository): array;
	public function getTemplateFieldName(): string;
}