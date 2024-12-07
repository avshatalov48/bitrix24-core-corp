<?php

namespace Bitrix\Tasks\Flow\Search\Conversion;

use Bitrix\Tasks\Flow\Search\Conversion\Converter\CreatorConverter;
use Bitrix\Tasks\Flow\Search\Conversion\Converter\GroupConverter;
use Bitrix\Tasks\Flow\Search\Conversion\Converter\IdConverter;
use Bitrix\Tasks\Flow\Search\Conversion\Converter\NameConverter;
use Bitrix\Tasks\Flow\Search\Conversion\Converter\OwnerConverter;

final class Factory
{
	private array $flow;

	public function __construct(array $flow)
	{
		$this->flow = $flow;
	}

	public function find(string $fieldName): ?AbstractConverter
	{
		$class = $this->getMap()[$fieldName] ?? null;

		if ($class === null)
		{
			return null;
		}

		return new $class($this->flow);
	}

	private function getMap(): array
	{
		$result = [];

		/** @var AbstractConverter $converter */
		foreach ($this->getConverters() as $converter)
		{
			$result[$converter::getFieldName()] = $converter;
		}

		return $result;
	}

	private function getConverters(): array
	{
		return [
			IdConverter::class,
			GroupConverter::class,
			NameConverter::class,
			OwnerConverter::class,
			CreatorConverter::class,
		];
	}
}