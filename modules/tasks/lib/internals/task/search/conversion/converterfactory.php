<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion;

use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\AccompliceConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\AuditorConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\CheckListConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\CrmConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\DescriptionConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\DirectorConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\GroupConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\IdConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\ResponsibleConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\TagConverter;
use Bitrix\Tasks\Internals\Task\Search\Conversion\Converters\TitleConverter;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\RepositoryInterface;

final class ConverterFactory
{
	public function __construct(private RepositoryInterface $repository)
	{
	}

	/** @throws SearchIndexException */
	public function find(string $taskFieldName): ?AbstractConverter
	{
		/** @var AbstractConverter $class */
		$class = $this->getMap()[$taskFieldName] ?? null;
		if (is_null($class))
		{
			return null;
		}
		return new $class($this->repository);
	}

	private function getMap(): array
	{
		$map = [];
		foreach ($this->getConverters() as $converterClass)
		{
			/** @var AbstractConverter $converterClass */
			$map[$converterClass::getFieldName()] = $converterClass;
		}

		return $map;
	}

	private function getConverters(): array
	{
		return [
			IdConverter::class,
			TitleConverter::class,
			DescriptionConverter::class,
			TagConverter::class,
			DirectorConverter::class,
			ResponsibleConverter::class,
			AuditorConverter::class,
			AccompliceConverter::class,
			GroupConverter::class,
			CheckListConverter::class,
			CrmConverter::class,
		];
	}
}