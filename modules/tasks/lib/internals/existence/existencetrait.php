<?php

namespace Bitrix\Tasks\Internals\Existence;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Existence\Exception\ExistenceCheckException;
use Bitrix\Tasks\Internals\Task\Template\TemplateCollection;
use Bitrix\Tasks\Internals\Task\Template\TemplateObject;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Internals\TaskCollection;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;

trait ExistenceTrait
{
	protected static int $existenceCheckLimit = 500;

	/**
	 * @throws ExistenceCheckException
	 */
	public function clearNonExistentEntities(): TemplateCollection|TaskCollection
	{
		if ($this->isEmpty())
		{
			return $this;
		}

		if ($this->count() > static::$existenceCheckLimit)
		{
			throw new ExistenceCheckException('Unable to execute query correctly - limit is exceeded');
		}

		/** @var TaskTable|TemplateTable $table */
		$table = static::$dataClass;

		try
		{
			$query = $table::query();
			$query
				->setSelect(['ID'])
				->whereIn('ID', $this->getIdList())
				->setLimit(static::$existenceCheckLimit);

			$ids = $query->exec()->fetchCollection()->getIdList();
		}
		catch (SystemException $exception)
		{
			throw new ExistenceCheckException($exception->getMessage());
		}

		$nonExistentIds = array_diff($this->getIdList(), $ids);
		
		foreach ($this as $entity)
		{
			/** @var TaskObject|TemplateObject $entity */
			if (in_array($entity->getId(), $nonExistentIds, true))
			{
				$this->remove($entity);
			}
		}
		
		return $this;
	}
}