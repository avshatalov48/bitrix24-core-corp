<?php

namespace Bitrix\Tasks\Provider\Tag;

use Bitrix\Main\ORM\Query\Result;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TagCollection;
use Bitrix\Tasks\Internals\Task\TagObject;
use Bitrix\Tasks\Provider\TaskQueryInterface;
use CDBResult;

class TagList
{
	private TaskQueryInterface $tagQuery;
	private ?TagCollection $collection = null;
	private Result $result;
	private array $tags;

	public function getList(TaskQueryInterface $tagQuery): CDBResult
	{
		$this->tagQuery = $tagQuery;
		return $this->initCollection()->prepareData()->getCDBResult();
	}

	public function getCollection(TaskQueryInterface $tagQuery): ?TagCollection
	{
		$this->tagQuery = $tagQuery;
		$this->initCollection();

		return $this->collection;
	}

	private function initCollection(): static
	{
		$query = TagQueryBuilder::build($this->tagQuery);
		$this->result = $query->exec();
		$this->collection = $this->result->fetchCollection();

		return $this;
	}

	private function prepareData(): static
	{
		$this->tags = [];
		foreach ($this->collection as $item)
		{
			$this->tags[] = $this->getTagData($item);
		}

		return $this;
	}

	private function getCDBResult(): CDBResult
	{
		$result = new CDBResult($this->result);
		$result->InitFromArray($this->tags);

		return $result;
	}

	private function getTagData(TagObject $object): array
	{
		$tag = [];
		foreach ($object->collectValues() as $field => $value)
		{
			if (in_array($field, $this->tagQuery->getSelect(), true))
			{
				$tag[$field] = $value;
			}

			if (
				in_array(LabelTable::getRelationAlias() . '.TASK_ID', $this->tagQuery->getSelect(), true)
				|| in_array('TASK_ID', $this->tagQuery->getSelect(), true)
			)
			{
				$tag['TASK_ID'] = $object->getTaskTag()->getTaskId();
			}
		}

		return $tag;
	}
}