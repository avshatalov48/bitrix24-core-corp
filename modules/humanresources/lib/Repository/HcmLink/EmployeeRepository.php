<?php

namespace Bitrix\HumanResources\Repository\HcmLink;

use Bitrix\Main\Error;
use Bitrix\HumanResources\Contract;
use Bitrix\HumanResources\Exception\CreationFailedException;
use Bitrix\HumanResources\Exception\UpdateFailedException;
use Bitrix\HumanResources\Exception\WrongStructureItemException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Item\Collection\HcmLink\EmployeeCollection;
use Bitrix\HumanResources\Model;
use Bitrix\HumanResources\Model\HcmLink\EmployeeTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class EmployeeRepository implements Contract\Repository\HcmLink\EmployeeRepository
{
	private const AVATAR_SIZE_HEIGHT = 36;
	private const AVATAR_SIZE_WIDTH = 36;
	private const AVATAR_DEFAULT_PATH = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';

	public function save(Item\HcmLink\Employee $item): Item\HcmLink\Employee
	{
		$model = $item->id
			? EmployeeTable::getById($item->id)->fetchObject()
			: $this->getModelByPersonUnique($item->personId, $item->code);

		$model = $this->fillModelFromItem($item, $model);

		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new CreationFailedException())
				->setErrors($result->getErrorCollection())
			;
		}

		return $this->getItemFromModel($model);
	}

	public function add(Item\HcmLink\Employee $employee): Item\HcmLink\Employee
	{
		$model = $this->fillModelFromItem($employee);
		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new CreationFailedException())->setErrors($result->getErrorCollection());
		}

		return $this->getItemFromModel($model);
	}

	public function update(Item\HcmLink\Employee $employee): Item\HcmLink\Employee
	{
		$model = EmployeeTable::getById($employee->id)->fetchObject();
		if ($model === null)
		{
			throw (new UpdateFailedException())->addError(new Error('Employee not found'));
		}

		$model = $this->fillModelFromItem($employee, $model);
		$result = $model->save();

		if ($result->isSuccess() === false)
		{
			throw (new UpdateFailedException())->setErrors($result->getErrorCollection());
		}

		return $employee;
	}

	/**
	 * @param Item\HcmLink\Employee $item
	 *
	 * @return Model\HcmLink\Employee
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	protected function fillModelFromItem(
		Item\HcmLink\Employee $item,
		?Model\HcmLink\Employee $model = null,
	): Model\HcmLink\Employee
	{
		$model = $model ?? EmployeeTable::createObject(true);
		$model->setCode($item->code)
			->setPersonId($item->personId)
			->setData($item->data)
		;

		if ($item->createdAt)
		{
			$model->setCreatedAt($item->createdAt);
		}

		return $model;
	}

	public function getByUnique(int $companyId, string $code): ?Item\HcmLink\Employee
	{
		return $this->getBy(['=PERSON.COMPANY_ID' => $companyId, '=CODE' => $code]);
	}

	public function getByPersonUnique(int $personId, string $code): ?Item\HcmLink\Employee
	{
		$model = $this->getModelByPersonUnique($personId, $code);

		return  $model ? $this->getItemFromModel($model): null;
	}

	protected function getModelByPersonUnique(int $personId, string $code): ?Model\HcmLink\Employee
	{
		$query = EmployeeTable::query()
					->setSelect(['*'])
					->setFilter(['=PERSON_ID' => $personId, '=CODE' => $code])
					->setLimit(1)
		;
		return $query->fetchObject();
	}

	protected function getItemFromModel(Model\HcmLink\Employee $model): Item\HcmLink\Employee
	{
		return new Item\HcmLink\Employee(
			personId: $model->getPersonId(),
			code: $model->getCode(),
			data: $model->getData(),
			createdAt: $model->getCreatedAt(),
			id: $model->hasId() ? $model->getId() : null,
		);
	}

	/**
	 * @param int[] $personIds
	 * @return EmployeeCollection
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws WrongStructureItemException
	 */
	public function getCollectionByPersonIds(array $personIds, ?int $limit = null): Item\Collection\HcmLink\EmployeeCollection
	{
		$query = EmployeeTable::query()
			->setSelect(['*'])
			->whereIn('PERSON_ID', $personIds)
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		$models = $query->fetchCollection()->getAll();
		$result = array_map([$this, 'getItemFromModel'], $models);

		return new Item\Collection\HcmLink\EmployeeCollection(...$result);
	}

	public function getSeveralByPersonIds(array $personIds): EmployeeCollection
	{
		$employeeWithSeveralPosition = EmployeeTable::query()
			->setSelect(['PERSON_ID'])
			->whereIn('PERSON_ID', $personIds)
			->setGroup(['PERSON_ID'])
			->registerRuntimeField('COUNT', new ExpressionField('PERSON_ID', 'COUNT(*)'))
			->having('COUNT', '>', 1)
			->fetchAll()
		;
		$filteredPersonIds = array_column($employeeWithSeveralPosition, 'PERSON_ID');
		$employees = EmployeeTable::query()
			->setSelect(['*'])
			->whereIn('PERSON_ID', $filteredPersonIds)
			->fetchCollection()
			->getAll()
		;
		$result = array_map([$this, 'getItemFromModel'], $employees);

		return new Item\Collection\HcmLink\EmployeeCollection(...$result);
	}

	public function hasSeveralByPersonIds(array $personIds): bool
	{
		$employeeWithSeveralPosition = EmployeeTable::query()
			->whereIn('PERSON_ID', $personIds)
			->setLimit(1)
			->setGroup(['PERSON_ID'])
			->registerRuntimeField('COUNT', new ExpressionField('PERSON_ID', 'COUNT(*)'))
			->having('COUNT', '>', 1)
			->fetch()
		;

		return $employeeWithSeveralPosition !== false;
	}

	public function getByPersonId(int $personId): Item\Collection\HcmLink\EmployeeCollection
	{
		$query = EmployeeTable::query()
			->setSelect(['*'])
			->where('PERSON_ID', $personId)
			->addOrder('ID', 'DESC')
		;

		return $this->toItemCollection($query->fetchCollection());
	}

	protected function getBy(array $filter): ?Item\HcmLink\Employee
	{
		$query = EmployeeTable::query()
			->setSelect(['*'])
			->setFilter($filter)
			->setLimit(1)
		;
		$model = $query->fetchObject();

		return  $model ? $this->getItemFromModel($model): null;
	}
	protected function toItemCollection(
		Model\HcmLink\EmployeeCollection $collection,
	): Item\Collection\HcmLink\EmployeeCollection
	{
		$result = [];
		foreach ($collection as $model) {
			$result[] = $this->getItemFromModel($model);
		}
		return new Item\Collection\HcmLink\EmployeeCollection(...$result);
	}

	public function getByIds(array $ids): EmployeeCollection
	{
		$models = EmployeeTable::query()
			->setSelect(['*'])
			->whereIn('ID', $ids)
			->fetchCollection()
			->getAll()
		;

		$result = array_map([$this, 'getItemFromModel'], $models);

		return new Item\Collection\HcmLink\EmployeeCollection(...$result);
	}

	public function deleteById(int $id): Result
	{
		EmployeeTable::delete($id);

		return new Result();
	}

	public function listMappedUserIdWithOneEmployeePosition(int $companyId, int ...$userIds): array
	{
		$result = EmployeeTable::query()
			->where('PERSON.COMPANY_ID', $companyId)
			->whereIn('PERSON.USER_ID', $userIds)
			->setGroup(['PERSON.USER_ID'])
			->registerRuntimeField('CNT', new ExpressionField('ROWS_CNT', 'COUNT(*)'))
			->registerRuntimeField('MIN_EMPLOYEE_ID', new ExpressionField('PERSON_ID', 'MIN(%s)', 'ID'))
			->having('CNT', '=', 1)
			->setSelect([
				'USER_ID' => 'PERSON.USER_ID',
				'MIN_EMPLOYEE_ID' => 'MIN_EMPLOYEE_ID',
			])
			->exec()
		;

		$employeesByUsers = [];
		while ($row = $result->fetch())
		{
			$userId = (int)$row['USER_ID'];
			$employeeId = (int)$row['MIN_EMPLOYEE_ID'];
			$employeesByUsers[$userId] = $employeeId;
		}

		return $employeesByUsers;
	}

	public function listMultipleVacancyEmployeesByUserIdsAndCompany(int $companyId, int ...$userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$employees = [];

		$subQuery = EmployeeTable::query()
			->where('PERSON.COMPANY_ID', $companyId)
			->whereIn('PERSON.USER_ID', $userIds)
			->having('CNT', '>', 1)
			->registerRuntimeField('CNT', new ExpressionField('ROWS_CNT', 'COUNT(*)'))
			->setGroup(['PERSON_ID'])
			->setSelect(['PERSON_ID'])
		;

		$employeesDb = EmployeeTable::query()
			->whereIn('PERSON_ID', $subQuery)
			->setSelect(['*', 'USER_ID' => 'PERSON.USER_ID', 'TITLE' => 'PERSON.TITLE'])
			->exec()
			->fetchAll()
		;

		$selectedUserIds = array_column($employeesDb, 'USER_ID');

		$usersMap = $this->getUsersMapById(...$selectedUserIds);

		foreach ($employeesDb as $item)
		{
			if (empty($item['DATA']['position']))
			{
				continue;
			}

			$userId = (int)$item['USER_ID'];
			if (!isset($employees[$userId]))
			{
				$avatarLink = $this->getAvatarLink($usersMap[$userId]['photo']);
				$employees[$userId] = [
					'userId' => $userId,
					'fullName' => $usersMap[$userId]['title'],
					'avatarLink' => $avatarLink,
					'positions' => [],
				];
			}

			$employees[$userId]['positions'][] = [
				'position' => (string)$item['DATA']['position'],
				'employeeId' => (int)$item['ID'],
			];
		}

		return array_values($employees);
	}

	public function getByPersonIds(array $personIds): EmployeeCollection
	{
		if (empty($personIds))
		{
			return new EmployeeCollection();
		}

		$models = EmployeeTable::query()
			->setSelect(['*'])
			->whereIn('PERSON_ID', $personIds)
			->fetchCollection()
			->getAll()
		;

		$result = array_map([$this, 'getItemFromModel'], $models);

		return new EmployeeCollection(...$result);

	}

	private function getAvatarLink(int $imageId): mixed
	{
		$avatarLink = self::AVATAR_DEFAULT_PATH;

		if ($imageId > 0)
		{
			$image = \CFile::resizeImageGet(
				$imageId,
				[
					'width' => self::AVATAR_SIZE_WIDTH,
					'height' => self::AVATAR_SIZE_HEIGHT,
				],
				BX_RESIZE_IMAGE_EXACT,
			);

			$avatarLink = !empty($image['src'])
				? $image['src']
				: self::AVATAR_DEFAULT_PATH
			;
		}

		return $avatarLink;
	}

	/**
	 * @param int[] $userIds
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUsersMapById(int ...$userIds): array
	{
		if (empty($userIds))
		{
			return [];
		}

		$result = [];

		$usersDb = UserTable::query()
			->setSelect(['ID', 'NAME', 'LAST_NAME', 'PERSONAL_PHOTO'])
			->whereIn('ID', $userIds)
			->fetchCollection()
			->getAll()
		;

		foreach ($usersDb as $user)
		{
			$result[$user->getId()] = [
				'title' => $user->getName() . ' ' . $user->getLastName(),
				'photo' => $user->getPersonalPhoto(),
			];
		}

		return $result;
	}
}
