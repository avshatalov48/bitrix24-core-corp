<?php

namespace Bitrix\Tasks\Replicator\Template\Conversion;

use ArrayIterator;
use IteratorAggregate;

final class ConverterCollection implements IteratorAggregate
{
	/** @var Converter[]  */
	private array $converters;

	public function __construct(Converter ...$converter)
	{
		$this->converters = $converter;
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->converters);
	}

	public function find(string $templateFieldName): ?Converter
	{
		foreach ($this->converters as $converter)
		{
			if ($converter->getTemplateFieldName() === $templateFieldName)
			{
				return $converter;
			}
		}

		return null;
	}
}