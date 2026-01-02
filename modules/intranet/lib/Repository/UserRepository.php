<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Exception\CreationFailedException;
use Bitrix\Intranet\Exception\DeleteFailedException;
use Bitrix\Intranet\Exception\UpdateFailedException;
use Bitrix\Intranet\Exception\WrongIdException;
use Bitrix\Intranet\Internal\Repository\Mapper\UserMapper;
use Bitrix\Intranet\Internals\Trait\UserUpdateError;
use Bitrix\Intranet\UserTable;
use Bitrix\Intranet\Contract\Repository\UserRepository as UserRepositoryContract;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\EO_User;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;

class UserRepository implements UserRepositoryContract
{
	use UserUpdateError;

	public function findUsersByLogins(array $logins): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->setSelect([
				'*',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findEmailOrShopUsersByLogins(array $logins): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}
		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->whereIn('EXTERNAL_AUTH_ID', ['email', 'shop'])
			->setSelect([
				'*',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findActivatedUsersByLogins(array $logins, array $notUserTypes = []): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}
		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->where(Query::filter()
				->logic('or')
				->whereNotIn('EXTERNAL_AUTH_ID', $notUserTypes)
				->whereNull('EXTERNAL_AUTH_ID'),
			)
			->where(Query::filter()
				->logic('or')
				->where('CONFIRM_CODE', '')
				->whereNull('CONFIRM_CODE')
			)
			->setSelect([
				'*',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function findInvitedUsersByLogins(array $logins, array $notUserTypes = []): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->where(Query::filter()
				->logic('or')
				->whereNotIn('EXTERNAL_AUTH_ID', $notUserTypes)
				->whereNull('EXTERNAL_AUTH_ID'),
			)
			->whereNot('CONFIRM_CODE', '')
			->setSelect([
				'*',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findRealUsersByLogins(array $logins, array $notUserTypes = []): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->where(Query::filter()
				->logic('or')
				->whereNotIn('EXTERNAL_AUTH_ID', $notUserTypes)
				->whereNull('EXTERNAL_AUTH_ID'),
			)
			->setSelect([
				'*',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findUsersByEmails(array $emails): UserCollection
	{
		if (empty($emails))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('EMAIL', $emails)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'UF_DEPARTMENT',
				'EXTERNAL_AUTH_ID',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findUsersByPhoneNumbers(array $phoneNumbers): UserCollection
	{
		if (empty($phoneNumbers))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('AUTH_PHONE_NUMBER', $phoneNumbers)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
				'UF_DEPARTMENT',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findUsersByIds(array $ids): UserCollection
	{
		if (empty($ids))
		{
			return new UserCollection();
		}
		$userList = UserTable::query()
			->whereIn('ID', $ids)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
				'UF_DEPARTMENT',
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function getUserById(int $id): User
	{
		$user = UserTable::query()
			->where('ID', $id)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
				'UF_DEPARTMENT',
			])
			->fetch()
		;

		if (!$user)
		{
			throw new ObjectNotFoundException('User not found');
		}

		return User::initByArray($user);
	}

	public function findUsersByLoginsAndEmails(array $emails): UserCollection
	{
		if (empty($emails))
		{
			return new UserCollection();
		}

		$fields = [
			'ID',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EXTERNAL_AUTH_ID',
			'ACTIVE',
			'CONFIRM_CODE',
			'LOGIN',
			'EMAIL',
			'UF_DEPARTMENT',
			'LANGUAGE_ID',
		];
		$userList = UserTable::query()
			->whereIn('LOGIN', $emails)
			->setSelect($fields)
			->union(
				UserTable::query()
					->whereIn('EMAIL', $emails)
					->setSelect($fields),
			)
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findUsersByLoginsAndPhoneNumbers(array $phoneNumbers): UserCollection
	{
		if (empty($phoneNumbers))
		{
			return new UserCollection();
		}

		$fields = [
			'ID',
			'NAME',
			'EXTERNAL_AUTH_ID',
			'LAST_NAME',
			'SECOND_NAME',
			'ACTIVE',
			'CONFIRM_CODE',
			'LOGIN',
			'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			'UF_DEPARTMENT',
		];
		$userList = UserTable::query()
			->whereIn('LOGIN', $phoneNumbers)
			->setSelect($fields)
			->union(
				UserTable::query()
					->whereIn('AUTH_PHONE_NUMBER', $phoneNumbers)
					->setSelect($fields),
			)
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findUsersByUserGroup(int $userGroup): UserCollection
	{
		if ($userGroup < 0)
		{
			return new UserCollection();
		}

		$userList = [];
		$dbUserList = \CGroup::GetGroupUserEx($userGroup);

		while($user = $dbUserList->fetch())
		{
			$user['ID'] = (int)$user['USER_ID'];
			$userList[] = $user;
		}

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findActiveUsersWithDepartmentsOnline(int $limitOnlineSeconds, int $limitRows = 0): UserCollection
	{
		$date = new DateTime();

		$fields = [
			'ID',
			'PERSONAL_PHOTO',
		];
		$userList = UserTable::query()
			->where('ACTIVE', true)
			->where('REAL_USER', 'expr', true)
			->where('LAST_ACTIVITY_DATE', '>=', $date->add('-' . $limitOnlineSeconds . ' seconds'))
			->where('UF_DEPARTMENT', '!=', false)
			->setSelect($fields)
			->setLimit($limitRows)
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
	}

	public function findActiveUsersWithDepartmentsOnlineCount(int $limitOnlineSeconds): int
	{
		$date = new DateTime();

		return (int)UserTable::query()
			->where('ACTIVE', true)
			->where('REAL_USER', 'expr', true)
			->where('LAST_ACTIVITY_DATE', '>=', $date->add('-' . $limitOnlineSeconds . ' seconds'))
			->where('UF_DEPARTMENT', '!=', false)
			->queryCountTotal()
		;
	}

	/**
	 * @param EO_User[] $modelCollection
	 * @return UserCollection
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function makeUserCollectionFromModelArray(array $modelCollection): UserCollection
	{
		$collection = new UserCollection();
		$mapper = new UserMapper();
		foreach ($modelCollection as $model)
		{
			$model['PASSWORD'] = null;
			$collection->add($mapper->convertFromArray($model));
		}

		return $collection;
	}

	/**
	 * @throws ArgumentException
	 */
	public function create(User $user): User
	{
		$userFields = [
			'LOGIN' => $user->getLogin(),
			'EMAIL' => $user->getEmail(),
			'PASSWORD' => \CUser::GeneratePasswordByPolicy($user->getGroupIds() ?? []),
			'CONFIRM_CODE' => $user->getConfirmCode(),
			'NAME' => $user->getName(),
			'LAST_NAME' => $user->getLastName(),
			'SECOND_NAME' => $user->getSecondName(),
			'GROUP_ID' => $user->getGroupIds(),
			'LID' => $user->getLid(), //SITE_ID
			'LANGUAGE_ID' => $user->getLanguageId() ?? (($site = \CSite::GetArrayByID($user->getLid())) ? $site['LANGUAGE_ID'] : LANGUAGE_ID),
			'PHONE_NUMBER' => $user->getPhoneNumber(),
			'PERSONAL_MOBILE' => $user->getPhoneNumber(),
			'ACTIVE' => $user->getActive(),
			'XML_ID' => $user->getXmlId(),
		];

		$userApi = new \CUser();
		$id = $userApi->Add($userFields);
		if ($id === false)
		{
			throw new CreationFailedException(new ErrorCollection([new Error($userApi->LAST_ERROR)]));
		}
		$user->setId((int)$id);

		return $user;
	}

	/**
	 * @throws UpdateFailedException
	 */
	public function update(User $user): User
	{
		$result = \CUser::GetByID($user->getId());
		if (!$result->fetch())
		{
			throw new UpdateFailedException(new ErrorCollection([new Error('User not found')]));
		}

		$userApi = new \CUser();
		$userData = (new UserMapper())->convertToArray($user);

		if (array_key_exists('PASSWORD', $userData) && empty($userData['PASSWORD']))
		{
			unset($userData['PASSWORD']);
		}

		if (!$userApi->Update($user->getId(), $userData))
		{
			throw new UpdateFailedException(new ErrorCollection([new Error($userApi->LAST_ERROR)]));
		}

		return $user;
	}

	/**
	 * @throws DeleteFailedException
	 * @throws ObjectNotFoundException
	 * @throws WrongIdException
	 */
	public function delete(User $user): void
	{
		if ($user->getId() <= 0)
		{
			throw new WrongIdException();
		}

		$result = \CUser::GetByID($user->getId());

		if (!$result->fetch())
		{
			throw new ObjectNotFoundException('User not found');
		}

		$userApi = new \CUser();

		if (!$userApi->delete($user->getId()))
		{
			throw new DeleteFailedException($this->getErrorCollectionFromUpdateLastError($userApi->LAST_ERROR));
		}
	}
}
