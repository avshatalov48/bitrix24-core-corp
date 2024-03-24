<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion;

use Bitrix\Tasks\Replication\RepositoryInterface;

interface ConverterInterface
{
	public function convert(RepositoryInterface $repository): array;
	public function getTemplateFieldName(): string;
}