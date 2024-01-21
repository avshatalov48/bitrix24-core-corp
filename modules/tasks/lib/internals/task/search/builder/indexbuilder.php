<?php

namespace Bitrix\Tasks\Internals\Task\Search\Builder;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Task\Search\Conversion\ConverterFactory;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\Search\IndexBuilderInterface;
use Bitrix\Tasks\Internals\Task\Search\Repository\TaskRepository;
use Bitrix\Tasks\UI;
use CSearch;

class IndexBuilder implements IndexBuilderInterface
{
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

		$this->makeUnique()->convertSpecialCharacters()->moveCharacters();

		return $this->index;
	}

	private function makeUnique(): static
	{
		$fields = explode(' ', $this->index);
		$fields = array_unique($fields);
		$this->index = implode(' ', $fields);

		return $this;
	}

	private function convertSpecialCharacters(): static
	{
		$this->index = UI::convertBBCodeToHtmlSimple($this->index);
		if (Loader::includeModule('search'))
		{
			$this->index = CSearch::killTags($this->index);
		}

		$this->index = mb_strtoupper(trim(str_replace(["\r", "\n", "\t"], ' ', $this->index)));

		return $this;
	}

	private function moveCharacters(): static
	{
		$this->index = str_rot13($this->index);
		return $this;
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