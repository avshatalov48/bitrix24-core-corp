<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\EO_User;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Item\User;

final class UserRepository
{
	public function getById(int $userId): ?User
	{
		$model = UserTable::getById($userId)->fetchObject();

		return ($model !== null) ? $this->extractItemFromModel($model) : null;
	}

	private function extractItemFromModel(EO_User $model): User
	{
		return new User(
			$model->getId(),
			$model->getName(),
			$model->getLastName(),
			$model->getSecondName(),
			$model->getPersonalPhoto()
		);
	}

}
