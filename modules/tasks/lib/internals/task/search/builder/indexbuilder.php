<?php

namespace Bitrix\Tasks\Internals\Task\Search\Builder;

use Bitrix\Tasks\Internals\Task\Search\Conversion\ConverterFactory;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\IndexBuilderInterface;
use Bitrix\Tasks\Internals\Task\Search\Repository\TaskRepository;

class IndexBuilder implements IndexBuilderInterface
{
	use SearchIndexTrait;

	private ConverterFactory $factory;
	private string $index = '';

	public function __construct(private int $taskId)
	{
		$this->factory = new ConverterFactory(new TaskRepository($this->taskId));
	}

	/**
	 * @throws SearchIndexException. Sure.
	 */
	public function build(): string
	{
		foreach ($this->getConvertableFields() as $field)
		{
			$converter = $this->factory->find($field);
			$this->index .= ' ' . (string)$converter?->convert() . ' ';
		}

		$this
			->convertSpecialCharacters()
			->encodeEmoji()
			->makeUnique()
			->moveCharacters();

		return $this->index;
	}

	private function getConvertableFields(): array
	{
		return [
			'ID',
			'TITLE',
			'DESCRIPTION',
			'TAGS',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'AUDITORS',
			'ACCOMPLICES',
			'GROUP_ID',
			'CHECKLIST',
			'UF_CRM_TASK',
		];
	}
}