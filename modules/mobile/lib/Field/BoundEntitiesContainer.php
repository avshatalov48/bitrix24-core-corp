<?php

namespace Bitrix\Mobile\Field;

final class BoundEntitiesContainer
{
	private static self $instance;
	private array $boundEntities = [];

	/**
	 * @return static
	 */
	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $boundEntities
	 * @return void
	 */
	public function addBoundEntities(array $boundEntities): void
	{
		foreach ($boundEntities as $entityName => $entities)
		{
			if (!isset($this->boundEntities[$entityName]))
			{
				$this->boundEntities[$entityName] = [];
			}

			foreach ($entities as $id => $entity)
			{
				if (!isset($this->boundEntities[$entityName][$id]))
				{
					$this->boundEntities[$entityName][$id] = $entity;
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getBoundEntities(): array
	{
		return $this->boundEntities;
	}
}