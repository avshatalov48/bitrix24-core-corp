<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion;

use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

interface ConverterInterface
{
	public function convert(RepositoryInterface $repository): array;
	public function getTemplateFieldName(): string;
}