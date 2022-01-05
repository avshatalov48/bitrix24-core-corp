<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Tasks\Scrum\Internal\TypeTable;

class TypeService implements Errorable
{
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_ITEM_TYPE_01';
	const ERROR_COULD_NOT_CREATE = 'TASKS_ITEM_TYPE_02';
	const ERROR_COULD_NOT_CHANGE = 'TASKS_ITEM_TYPE_03';
	const ERROR_COULD_NOT_REMOVE = 'TASKS_ITEM_TYPE_04';
	const ERROR_COULD_NOT_READ = 'TASKS_ITEM_TYPE_05';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Checks if types have already been created for the entity.
	 *
	 * @param int $entityId Entity id.
	 * @return bool
	 */
	public function isEmpty(int $entityId): bool
	{
		try
		{
			$queryObject = TypeTable::getList([
				'select' => ['ID'],
				'filter' => [
					'ENTITY_ID' => $entityId,
				],
			]);

			return !((bool) $queryObject->fetch());
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_IS_EMPTY
				)
			);

			return false;
		}
	}

	/**
	 * Returns an object of type.
	 *
	 * @param array $fields Fields to create object.
	 * @return TypeTable
	 */
	public function getTypeObject(array $fields = []): TypeTable
	{
		return TypeTable::createType($fields);
	}

	public function getType(int $typeId): TypeTable
	{
		$type = $this->getTypeObject();

		try
		{
			$queryObject = TypeTable::getList([
				'select' => ['*'],
				'filter' => [
					'ID' => $typeId,
				],
			]);
			while ($typeData = $queryObject->fetch())
			{
				$type = $this->getTypeObject($typeData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ
				)
			);
		}

		return $type;
	}

	/**
	 * Creates a type.
	 *
	 * @param TypeTable $type
	 * @return TypeTable
	 */
	public function createType(TypeTable $type): TypeTable
	{
		try
		{
			$result = TypeTable::add($type->getFieldsToCreate());

			if ($result->isSuccess())
			{
				$type->setId($result->getId());
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_CREATE);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_CREATE
				)
			);
		}

		return $type;
	}

	/**
	 * Changes the type.
	 *
	 * @param TypeTable $type The type.
	 * @return bool
	 */
	public function changeType(TypeTable $type): bool
	{
		try
		{
			$result = TypeTable::update($type->getId(), $type->getFieldsToUpdate());

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_CHANGE);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_CHANGE
				)
			);

			return false;
		}
	}

	/**
	 * Removes the type.
	 *
	 * @param TypeTable $type The type.
	 * @return bool
	 */
	public function removeType(TypeTable $type): bool
	{
		try
		{
			$result = TypeTable::delete($type->getId());

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE
				)
			);

			return false;
		}
	}

	/**
	 * Returns types based on entity id.
	 *
	 * @param int $entityId The types entity id.
	 * @return TypeTable[]
	 */
	public function getTypes(int $entityId): array
	{
		$types = [];

		try
		{
			$queryObject = TypeTable::getList([
				'select' => ['*'],
				'filter' => [
					'ENTITY_ID' => $entityId,
				],
				'order' => [
					'SORT' => 'ASC',
				],
			]);
			while ($typeData = $queryObject->fetch())
			{
				$types[] = $this->getTypeObject($typeData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_IS_EMPTY
				)
			);
		}

		return $types;
	}

	/**
	 * Returns an array of data in the required format for the client app.
	 *
	 * @param TypeTable $type The type object.
	 * @return array
	 */
	public function getTypeData(TypeTable $type): array
	{
		return [
			'id' => $type->getId(),
			'name' => $type->getName(),
			'sort' => $type->getSort(),
			'dodRequired' => $type->getDodRequired(),
		];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(
			new Error(
				implode('; ', $result->getErrorMessages()),
				$code
			)
		);
	}
}