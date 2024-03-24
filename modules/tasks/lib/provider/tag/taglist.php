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

	private ?array $tags = null;
	private array $storedTags;

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
		$this->tags = $query->exec()->fetchAll();

		return $this;
	}

	private function prepareData(): static
	{
		$this->storedTags = [];
		foreach ($this->tags as $data)
		{
			$this->storedTags[] = $this->getTagData(TagObject::wakeUpObject($data));
		}

		return $this;
	}

	private function getCDBResult(): CDBResult
	{
		$result = new CDBResult($this->result);
		$result->InitFromArray($this->storedTags);

		return $result;
	}

	private function getTagData(TagObject $object): array
	{
		$tag = [];
		$values = array_merge($object->collectValues(), $object->customData->getValues());
		foreach ($values as $field => $value)
		{
			$tag[$field] = $value;
			// if (in_array($field, $this->tagQuery->getSelect(), true))
			// {
			//
			// }
			//
			// if (
			// 	in_array('TASK_ID', $this->tagQuery->getSelect(), true)
			// )
			// {
			// 	$tag['TASK_ID'] = (int)$value;
			// }
		}

		return $tag;
	}
}