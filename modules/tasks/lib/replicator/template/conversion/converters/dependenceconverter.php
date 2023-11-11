<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Repository;

final class DependenceConverter implements Converter
{
	public function convert(Repository $repository): array
	{
		$taskFields = [];

		$dependencies = $repository->getTemplate()->getDependencies();
		foreach ($dependencies as $dependence)
		{
			$taskFields[$this->getTaskFieldName()][] = $dependence->getDependsOnId();
		}

		return $taskFields;
	}

	public function getTaskFieldName(): string
	{
		return 'DEPENDS_ON';
	}

	public function getTemplateFieldName(): string
	{
		return 'DEPENDENCIES';
	}
}