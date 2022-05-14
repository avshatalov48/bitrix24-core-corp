<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;
use Bitrix\Tasks\Scrum\Form\TypeForm;
use Bitrix\Tasks\Scrum\Internal\TypeParticipantsTable;
use Bitrix\Tasks\Scrum\Internal\TypeTable;

class TypeService implements Errorable
{
	const ERROR_COULD_NOT_IS_EMPTY = 'TASKS_ITEM_TYPE_01';
	const ERROR_COULD_NOT_CREATE = 'TASKS_ITEM_TYPE_02';
	const ERROR_COULD_NOT_CHANGE = 'TASKS_ITEM_TYPE_03';
	const ERROR_COULD_NOT_REMOVE = 'TASKS_ITEM_TYPE_04';
	const ERROR_COULD_NOT_READ = 'TASKS_ITEM_TYPE_05';
	const ERROR_COULD_NOT_ADD_PARTICIPANTS = 'TASKS_ITEM_TYPE_06';

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

	public function getType(int $typeId): TypeForm
	{
		$type = new TypeForm();

		try
		{
			$queryObject = TypeTable::getList([
				'select' => ['*', 'PARTICIPANTS'],
				'filter' => [
					'ID' => $typeId,
				],
			]);
			while ($typeObject = $queryObject->fetchObject())
			{
				$type->fillFromDatabase($typeObject->collectValues(Values::ACTUAL));

				$type->setParticipantsCodes($typeObject->getParticipants()->getCodeList());
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
	 * @param TypeForm $typeForm The type form object.
	 * @return TypeForm
	 */
	public function createType(TypeForm $typeForm): TypeForm
	{
		try
		{
			$result = TypeTable::add($typeForm->getFieldsToCreate());

			if ($result->isSuccess())
			{
				$typeForm->setId($result->getId());
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

		return $typeForm;
	}

	/**
	 * Changes the type.
	 *
	 * @param TypeForm $typeForm The type form object.
	 * @return bool
	 */
	public function changeType(TypeForm $typeForm): bool
	{
		try
		{
			$result = TypeTable::update($typeForm->getId(), $typeForm->getFieldsToUpdate());

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
	 * Saves the type participants.
	 *
	 * @param TypeForm $typeForm The type form object.
	 * @return bool
	 */
	public function saveParticipants(TypeForm $typeForm): bool
	{
		try
		{
			$participants = $typeForm->getParticipantsCodes();

			$this->removeParticipants($typeForm);

			foreach ($participants as $code)
			{
				TypeParticipantsTable::add([
					'TYPE_ID' => $typeForm->getId(),
					'CODE' => $code,
				]);
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_ADD_PARTICIPANTS
				)
			);

			return false;
		}
	}

	/**
	 * Removes the type.
	 *
	 * @param TypeForm $typeForm The type form object.
	 * @return bool
	 */
	public function removeType(TypeForm $typeForm): bool
	{
		try
		{
			$result = TypeTable::delete($typeForm->getId());

			if ($result->isSuccess())
			{
				$this->removeParticipants($typeForm);

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
	 * @return TypeForm[]
	 */
	public function getTypes(int $entityId): array
	{
		$types = [];

		try
		{
			$queryObject = TypeTable::getList([
				'select' => ['*', 'PARTICIPANTS'],
				'filter' => [
					'ENTITY_ID' => $entityId,
				],
				'order' => [
					'SORT' => 'ASC',
					'PARTICIPANTS.ID' => 'ASC',
				],
			]);
			while ($typeObject = $queryObject->fetchObject())
			{
				$type = new TypeForm();

				$type->fillFromDatabase($typeObject->collectValues(Values::ACTUAL));

				$type->setParticipantsCodes($typeObject->getParticipants()->getCodeList());

				$types[] = $type;
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

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function removeParticipants(TypeForm $typeForm): void
	{
		$queryObject = TypeParticipantsTable::getList([
			'filter' => [
				'=TYPE_ID' => $typeForm->getId(),
			]
		]);
		while ($participant = $queryObject->fetch())
		{
			TypeParticipantsTable::delete($participant['ID']);
		}
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