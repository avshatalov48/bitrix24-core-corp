<?php

namespace Bitrix\Tasks\Access\Model;

use Bitrix\Tasks\Access\AccessibleTag;
use Bitrix\Tasks\Internals\Registry\TagRegistry;


class TagModel implements AccessibleTag
{
	private int $id;
	private static array $cache = [];

	public static function createFromId(int $itemId): self
	{
		if (!array_key_exists($itemId, self::$cache))
		{
			$model = new self();
			$model->setId($itemId);
			self::$cache[$itemId] = $model;
		}

		return self::$cache[$itemId];
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	private function getTag(): ?array
	{
		if (!$this->id)
		{
			return null;
		}

		return TagRegistry::getInstance()->get($this->id);
	}
	public function getOwner(): int
	{
		$tag = $this->getTag();
		if (!$tag)
		{
			return 0;
		}

		return $tag['USER_ID'];
	}

	public static function invalidate(?int $tagId = null): void
	{
		if (is_null($tagId))
		{
			static::$cache = [];
		}
		else
		{
			unset(self::$cache[$tagId]);
		}

		TagRegistry::getInstance()->invalidate($tagId);
	}
}