<?php

namespace Bitrix\Tasks\Internals\Registry;

use Bitrix\Tasks\Internals\Task\LabelTable;

class TagRegistry
{
	private static $instance;

	private array $storage = [];

	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get(int $tagId): ?array
	{
		if (!array_key_exists($tagId, $this->storage))
		{
			$this->load($tagId);
		}
		if (!isset($this->storage[$tagId]))
		{
			return null;
		}

		return $this->storage[$tagId];
	}

	public function load($tagIds): self
	{
		if (empty($tagIds))
		{
			return $this;
		}

		if (!is_array($tagIds))
		{
			$tagIds = [$tagIds];
		}

		$tagIds = array_diff(array_unique($tagIds), array_keys($this->storage));

		if (empty($tagIds))
		{
			return $this;
		}

		$tags = LabelTable::getList([
			'select' => [
				'*',
			],
			'filter' => [
				'=ID' => $tagIds,
			],
		])->fetchAll();

		if (empty($tags))
		{
			return $this;
		}

		foreach ($tags as $tag)
		{
			$this->storage[$tag['ID']] = $tag;
		}

		return $this;
	}

	public function invalidate(?int $tagId = null): void
	{
		if (is_null($tagId))
		{
			$this->storage = [];
		}
		else
		{
			unset($this->storage[$tagId]);
		}
	}
}