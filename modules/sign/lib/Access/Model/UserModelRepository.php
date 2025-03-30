<?php

namespace Bitrix\Sign\Access\Model;

class UserModelRepository
{
	public function getByUserId(int $userId): UserModel
	{
		return UserModel::createFromId($userId);
	}
}