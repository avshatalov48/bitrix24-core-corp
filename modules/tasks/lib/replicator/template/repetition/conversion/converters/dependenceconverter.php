<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

final class DependenceConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields = [];

		$dependencies = $repository->getEntity()->getDependencies();
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