<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

class UserRepository extends BaseRepository
{
	/**
	 * @param array $userIdList
	 * @return list<array{
	 *     ID: string,
	 *     NAME: string,
	 *     LAST_NAME: string,
	 *     PERSONAL_GENDER: string,
	 *     PERSONAL_PHOTO: string,
	 *     SECOND_NAME: string,
	 *     EMAIL: string,
	 *     LOGIN: string,
	 * }>
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMainUserData(array $userIdList): array
	{
		return UserTable::query()
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'PERSONAL_GENDER',
				'PERSONAL_PHOTO',
				'SECOND_NAME',
				'EMAIL',
				'LOGIN',
			])
			->whereIn('ID', $userIdList)
			->fetchAll()
		;
	}
}
