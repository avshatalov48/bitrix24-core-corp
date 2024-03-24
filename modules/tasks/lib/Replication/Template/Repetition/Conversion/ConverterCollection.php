<?php

namespace Bitrix\Tasks\Replication\Template\Repetition\Conversion;

use ArrayIterator;
use IteratorAggregate;

final class ConverterCollection implements IteratorAggregate
{
	/** @var ConverterInterface[]  */
	private array $converters;

	public function __construct(ConverterInterface ...$converter)
	{
		$this->converters = $converter;
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->converters);
	}

	public function find(string $templateFieldName): ?ConverterInterface
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