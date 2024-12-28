<?php

namespace Bitrix\BIConnector\Integration\Superset\Repository;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\UserTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\BIConnector\Integration\Superset\Integrator\Dto\User;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;

final class SupersetUserRepository
{
	public function getById(int $id): ?User
	{
		$query = new Query(UserTable::getEntity());
		$query
			->setSelect([
				'ID',
				'LOGIN',
				'EMAIL',
				'NAME',
				'LAST_NAME',
				'SUPERSET_CLIENT_ID' => 'SUPERSET_USER.CLIENT_ID',
				'SUPERSET_PERMISSION_HASH' => 'SUPERSET_USER.PERMISSION_HASH',
			])
			->setFilter([
				'=ID' => $id,
				'=IS_REAL_USER' => 'Y',
			])
			->setLimit(1)
			->registerRuntimeField(
				new Reference(
					'SUPERSET_USER',
					SupersetUserTable::class,
					Join::on('this.ID', 'ref.USER_ID'),
					['join_type' => Join::TYPE_LEFT]
				)
			)
			->setCacheTtl(86400)
		;

		$result = $query->exec();
		$user = $result->fetch();
		if ($user)
		{
			$email = $user['EMAIL'] ?: ($user['LOGIN'] . '@bitrix.bi');

			return new User(
				id: $user['ID'],
				userName: $email,
				email: $email,
				firstName: $user['NAME'] ?: $user['LOGIN'],
				lastName: $user['LAST_NAME'] ?: $user['LOGIN'],
				clientId: $user['SUPERSET_CLIENT_ID'] ?: null,
				permissionHash: $user['SUPERSET_PERMISSION_HASH'] ?: null,
			);
		}

		return null;
	}

	public function getAdmin(): ?User
	{
		static $result = null;
		if ($result)
		{
			return $result;
		}

		$user = UserGroupTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=GROUP_ID' => 1,
				'=DATE_ACTIVE_TO' => null,
				'=USER.ACTIVE' => 'Y',
				'=USER.IS_REAL_USER' => 'Y',
			],
			'order' => ['USER_ID' => 'ASC'],
			'limit' => 1,
		])
			->fetch()
		;

		if ($user)
		{
			$result = $this->getById((int)$user['USER_ID']);

			return $result;
		}

		return null;
	}
}
