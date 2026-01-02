<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Infrastructure\Controller\AutoWire;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\UserTable;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\ORM\Query\Query;

trait UserParameterTrait
{
	protected function createUserParameter(): ExactParameter
	{
		return new ExactParameter(
			User::class,
			'user',
			function($className, ?int $userId = null): ?User {
				if (!$userId)
				{
					return null;
				}

				$userData = $this->getUserQuery()
					->where('ID', $userId)
					->fetch();

				return is_array($userData) ? User::initByArray($userData) : null;
			}
		);
	}

	private function getUserQuery(): Query
	{
		return UserTable::query()
			->setSelect([
				'ID',
				'NAME',
				'LAST_NAME',
				'ACTIVE',
				'CONFIRM_CODE',
				'LOGIN',
				'EMAIL',
				'LAST_LOGIN'
			]);
	}
}
