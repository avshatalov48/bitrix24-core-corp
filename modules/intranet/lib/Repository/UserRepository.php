<?php

namespace Bitrix\Intranet\Repository;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\UserTable;
use Bitrix\Intranet\Contract\Repository\UserRepository as UserRepositoryContract;
use Bitrix\Main\EO_User;

class UserRepository implements UserRepositoryContract
{
	public function findUsersByLogins(array $logins): UserCollection
	{
		if (empty($logins))
		{
			return new UserCollection();
		}

		$userList = UserTable::query()
			->whereIn('LOGIN', $logins)
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'UF_DEPARTMENT',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER'
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
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
				'UF_DEPARTMENT'
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
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'EXTERNAL_AUTH_ID',
				'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
				'UF_DEPARTMENT'
			])
			->fetchAll()
		;

		return $this->makeUserCollectionFromModelArray($userList);
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
			'EXTERNAL_AUTH_ID',
			'ACTIVE',
			'CONFIRM_CODE',
			'LOGIN',
			'EMAIL',
			'UF_DEPARTMENT'
		];
		$userList = UserTable::query()
			->whereIn('LOGIN', $emails)
			->setSelect($fields)
			->union(
				UserTable::query()
					->whereIn('EMAIL', $emails)
					->setSelect($fields)
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
			'ACTIVE',
			'CONFIRM_CODE',
			'LOGIN',
			'AUTH_PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',
			'UF_DEPARTMENT'
		];
		$userList = UserTable::query()
			->whereIn('LOGIN', $phoneNumbers)
			->setSelect($fields)
			->union(
				UserTable::query()
					->whereIn('AUTH_PHONE_NUMBER', $phoneNumbers)
					->setSelect($fields)
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
		$dbUserList = \CAllGroup::GetGroupUserEx($userGroup);

		while($user = $dbUserList->fetch())
		{
			$userList[] = $user;
		}

		return $this->makeUserCollectionFromModelArray($userList);
	}

	/**
	 * @param EO_User[] $modelCollection
	 * @return UserCollection
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function makeUserCollectionFromModelArray(array $modelCollection): UserCollection
	{
		$collection = new UserCollection();
		foreach ($modelCollection as $model)
		{
			$collection->add(User::initByArray($model));
		}

		return $collection;
	}
}