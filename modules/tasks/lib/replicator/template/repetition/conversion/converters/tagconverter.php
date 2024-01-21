<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Repetition\Conversion\ConverterInterface;
use Bitrix\Tasks\Replicator\Template\RepositoryInterface;

final class TagConverter implements ConverterInterface
{
	public function convert(RepositoryInterface $repository): array
	{
		$taskFields = [];

		$tags = $repository->getEntity()->getTagList();
		foreach ($tags as $tag)
		{
			$taskFields[] = $tag->getName();
		}

		return [$this->getTaskFieldName() => $taskFields];
	}

	public function getTaskFieldName(): string
	{
		return 'TAGS';
	}

	public function getTemplateFieldName(): string
	{
		return 'TAG_LIST';
	}
}