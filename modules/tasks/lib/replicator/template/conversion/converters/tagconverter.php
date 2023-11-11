<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion\Converters;

use Bitrix\Tasks\Replicator\Template\Conversion\Converter;
use Bitrix\Tasks\Replicator\Template\Repository;

final class TagConverter implements Converter
{
	public function convert(Repository $repository): array
	{
		$taskFields = [];

		$tags = $repository->getTemplate()->getTagList();
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