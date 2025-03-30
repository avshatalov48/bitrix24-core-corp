<?php

namespace Bitrix\Extranet\Repository;

use Bitrix\Extranet\Entity;
use Bitrix\Extranet\Model\ExtranetUserTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Extranet\Contract;
use Bitrix\Extranet\Enum;
use Bitrix\Extranet\Model;
use Bitrix\Main\UserTable;
use Exception;
use Bitrix\HumanResources;

class ExtranetUserRepository implements Contract\Repository\ExtranetUserRepository
{
	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByUserId(int $userId): ?Entity\ExtranetUser
	{
		$extranetUserModel = $this->getExtranetUserModelByUserId($userId);

		return $extranetUserModel ? $this->mapModelToEntity($extranetUserModel) : null;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getById(int $id): ?Entity\ExtranetUser
	{
		$extranetUserModel = $this->getExtranetUserModelById($id);

		return $extranetUserModel ? $this->mapModelToEntity($extranetUserModel) : null;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAll(int $limit = 100): Entity\Collection\ExtranetUserCollection
	{
		$modelCollection = ExtranetUserTable::query()
			->setSelect(['*', 'USER'])
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->mapModelCollectionToEntityCollection($modelCollection);
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAllByRole(Enum\User\ExtranetRole $role, int $limit = 100): Entity\Collection\ExtranetUserCollection
	{
		$modelCollection = ExtranetUserTable::query()
			->setSelect(['*', 'USER'])
			->where('ROLE', $role->value)
			->setLimit($limit)
			->fetchCollection()
		;

		return $this->mapModelCollectionToEntityCollection($modelCollection);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAllUserIds(): array
	{
		$result = ExtranetUserTable::query()
			->setSelect(['USER_ID'])
			->exec()
			->fetchAll()
		;

		return array_map(static fn($item) => (int)$item['USER_ID'], $result);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAllUserIdsByRole(Enum\User\ExtranetRole $role): array
	{
		$result = ExtranetUserTable::query()
			->setSelect(['USER_ID'])
			->where('ROLE', $role->value)
			->exec()
			->fetchAll()
		;

		return array_map(static fn($item) => (int)$item['USER_ID'], $result);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getAllUserIdsByRoles(array $roles): array
	{
		$roleFilter = [];

		foreach ($roles as $role)
		{
			$roleFilter[] = $role->value;
		}

		$result = ExtranetUserTable::query()
			->setSelect(['USER_ID'])
			->whereIn('ROLE', $roleFilter)
			->exec()
			->fetchAll()
		;

		return array_map(static fn($item) => (int)$item['USER_ID'], $result);
	}

	/**
	 * @throws Exception
	 */
	public function upsert(Entity\ExtranetUser $extranetUser): Result
	{
		if ($extranetUser->getRole()->isFormerExtranet())
		{
			$isAvailableUserId = $this->isAvailableUserIdForFormerRole($extranetUser->getUserId());
		}
		else
		{
			$isAvailableUserId = $this->isAvailableUserId($extranetUser->getUserId());
		}

		if (!$isAvailableUserId)
		{
			return (new Result())->addError(new Error('User can`t have extranet type'));
		}

		$extranetUserModel = $this->getExtranetUserModelByUserId($extranetUser->getUserId());

		if (!$extranetUserModel)
		{
			return $this->add($extranetUser);
		}

		return $this->update($extranetUser);
	}

	/**
	 * @throws Exception
	 */
	public function add(Entity\ExtranetUser $extranetUser): Result
	{
		$extranetUserModel = $this->getExtranetUserModelById($extranetUser->getUserId());

		if ($extranetUserModel)
		{
			return (new Result())->addError(new Error('Extranet user already added'));
		}

		$isAvailableUserId = $this->isAvailableUserId($extranetUser->getUserId());

		if (!$isAvailableUserId)
		{
			return (new Result())->addError(new Error('User can`t have extranet type'));
		}

		return ExtranetUserTable::add([
			'USER_ID' => $extranetUser->getUserId(),
			'ROLE' => $extranetUser->getRole()->value,
			'CHARGEABLE' => $extranetUser->isChargeable(),
		]);
	}

	/**
	 * @throws Exception
	 */
	public function update(Entity\ExtranetUser $extranetUser): Result
	{
		$extranetUserModel = $this->getExtranetUserModelByUserId($extranetUser->getUserId());

		if ($extranetUserModel === null)
		{
			return (new Result())->addError(new Error('Extranet user not found'));
		}

		return $extranetUserModel->setRole($extranetUser->getRole()->value)
			->setChargeable($extranetUser->isChargeable())
			->save()
		;
	}

	/**
	 * @throws Exception
	 */
	public function deleteById(int $id): Result
	{
		return ExtranetUserTable::delete($id);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function deleteByUserId(int $userId): Result
	{
		$extranetUserModel = $this->getExtranetUserModelForDeleteByUserId($userId);

		if ($extranetUserModel === null)
		{
			return (new Result())->addError(new Error('Extranet user not found'));
		}

		return $extranetUserModel->delete();
	}

	private function mapModelToEntity(Model\EO_ExtranetUser $model): Entity\ExtranetUser
	{
		return (new Model\Mapper\ExtranetUser())->map($model);
	}

	private function mapModelCollectionToEntityCollection(
		Model\EO_ExtranetUser_Collection $modelCollection,
	): Entity\Collection\ExtranetUserCollection
	{
		return (new Model\Mapper\ExtranetUserCollection())->map($modelCollection);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getExtranetUserModelById(int $id): ?Model\EO_ExtranetUser
	{
		return ExtranetUserTable::query()
			->setSelect(['*', 'USER'])
			->addFilter('=ID', $id)
			->setLimit(1)
			->fetchObject()
		;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getExtranetUserModelByUserId(int $userId): ?Model\EO_ExtranetUser
	{
		return ExtranetUserTable::query()
			->setSelect(['*', 'USER'])
			->addFilter('=USER_ID', $userId)
			->setLimit(1)
			->fetchObject()
		;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getExtranetUserModelForDeleteByUserId(int $userId): ?Model\EO_ExtranetUser
	{
		return ExtranetUserTable::query()
			->setSelect(['ID', 'USER_ID'])
			->addFilter('=USER_ID', $userId)
			->setLimit(1)
			->fetchObject()
		;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function isAvailableUserId(int $id): bool
	{
		$isRealUser = (bool) UserTable::query()
			->setSelect(['ID'])
			->addFilter('=ID', $id)
			->addFilter('=IS_REAL_USER', 'Y')
			->addFilter('GROUPS.GROUP_ID', \CExtranet::GetExtranetUserGroupID())
			->setLimit(1)
			->exec()
			->fetch()
		;

		return $isRealUser
			&& Loader::includeModule('humanresources')
			&& !HumanResources\Service\Container::getUserService()->isEmployee($id);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function isAvailableUserIdForFormerRole(int $id): bool
	{
		return (bool) UserTable::query()
			->setSelect(['ID'])
			->addFilter('=ID', $id)
			->addFilter('=IS_REAL_USER', 'Y')
			->setLimit(1)
			->exec()
			->fetch()
		;
	}
}
