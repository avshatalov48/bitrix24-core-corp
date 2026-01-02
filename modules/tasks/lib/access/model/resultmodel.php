<?php

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;

class ResultModel implements AccessibleItem
{
	private int $id;
	private ?int $taskId = null;
	private int $createdBy = 0;
	private static array $cache = [];

	public static function createFromId(int $itemId): self
	{
		if (array_key_exists($itemId, self::$cache))
		{
			return self::$cache[$itemId];
		}

		$model = new self();
		$result = ResultTable::getByPrimary($itemId)->fetchObject();
		if (is_null($result))
		{
			$model->setId(0);
			return $model;
		}
		$model->setId($itemId);
		$model->setCreatedBy($result->getCreatedBy());
		self::$cache[$itemId] = $model;

		return self::$cache[$itemId];
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getTaskId(): ?int
	{
		return $this->taskId;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	private function setCreatedBy(int $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getCreatedBy(): int
	{
		return $this->createdBy;
	}

	public static function invalidate(): void
	{
		static::$cache = [];
	}

	public static function createFromArray(array|Arrayable $data): static
	{
		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		$model = new static();

		$reflection = new \ReflectionClass($model);

		foreach ($data as $key => $value)
		{
			if ($reflection->hasProperty($key))
			{
				$model->{$key} = $value;
			}
		}

		return $model;
	}
}