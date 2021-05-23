<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Tasks\Scrum\Internal\EntityTable;

class EntityService implements Errorable
{
	const ERROR_COULD_NOT_READ_ENTITY = 'TASKS_ES_01';

	private $errorCollection;

	private static $entitiesById = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Returns an object with entity data by entity id.
	 *
	 * @param int $entityId Entity id.
	 * @return EntityTable
	 */
	public function getEntityById(int $entityId): EntityTable
	{
		if (array_key_exists($entityId, self::$entitiesById))
		{
			return self::$entitiesById[$entityId];
		}

		self::$entitiesById[$entityId] = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => (int)$entityId,
				],
			]);
			if ($entityData = $queryObject->fetch())
			{
				self::$entitiesById[$entityId] = EntityTable::createEntityObject($entityData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ENTITY));
		}

		return self::$entitiesById[$entityId];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}