<?php

namespace Bitrix\Tasks\Flow\Search;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Search\Conversion\Factory;
use Bitrix\Tasks\Internals\Task\Search\Builder\SearchIndexTrait;
use Bitrix\Tasks\Internals\Task\Search\IndexBuilderInterface;

class IndexBuilder implements IndexBuilderInterface
{
	use SearchIndexTrait;
	private Factory $factory;
	private string $index = '';

	public function __construct(Flow $flow)
	{
		$this->factory = new Factory($flow->toArray());
	}

	public function build(): string
	{
		foreach ($this->getConvertableFields() as $field)
		{
			$converter = $this->factory->find($field);
			$this->index .= ' ' . $converter?->convert();
		}

		$this->convertSpecialCharacters();
		$this->encodeEmoji();
		$this->makeUnique();
		$this->moveCharacters();

		return $this->index;
	}

	private function getConvertableFields(): array
	{
		return [
			'id',
			'name',
			'creatorId',
			'ownerId',
			'groupId',
		];
	}
}