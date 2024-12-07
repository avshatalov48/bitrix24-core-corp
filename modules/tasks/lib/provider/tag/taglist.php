<?php

namespace Bitrix\Tasks\Provider\Tag;

use Bitrix\Main\ORM\Query\Result;
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

		return $this->initArray()->prepareData()->getCDBResult();
	}

	public function getCollection(TaskQueryInterface $tagQuery): TagCollection
	{
		$this->tagQuery = $tagQuery;

		return $this->initCollection()->collection;
	}

	private function initCollection(): static
	{
		$this->collection = $this->getOrmResult()->fetchCollection();

		return $this;
	}

	private function initArray(): static
	{
		$this->tags = $this->getOrmResult()->fetchAll();

		return $this;
	}

	private function getOrmResult(): Result
	{
		$query = TagQueryBuilder::build($this->tagQuery);
		$this->result = $query->exec();

		return $this->result;
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
		}

		return $tag;
	}
}