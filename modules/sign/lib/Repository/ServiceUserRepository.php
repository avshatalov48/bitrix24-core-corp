<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Internal\ServiceUser\ServiceUser as ServiceUserModel;
use Bitrix\Sign\Internal\ServiceUser\ServiceUserTable;
use Bitrix\Sign\Item\B2e\ServiceUser;

class ServiceUserRepository
{
	public function add(ServiceUser $item)
	{
		$model = new ServiceUserModel();
		$model = $this->getFilledModelFromItem($item, $model);

		return $model
			->setDateCreate(new DateTime())
			->save()
		;
	}

	public function getByUserId(int $userId): ?ServiceUser
	{
		$model = ServiceUserTable::query()
			->where('USER_ID', $userId)
			->setSelect(['*'])
			->setLimit(1)
			->fetchObject()
		;
		if ($model instanceof ServiceUserModel)
		{
			return $this->extractItemFromModel($model);
		}
		return null;
	}

	private function extractItemFromModel(ServiceUserModel $model): ServiceUser
	{
		return new ServiceUser(
			userId: $model->getUserId(),
			uid: $model->getUid(),
		);
	}

	private function getFilledModelFromItem(ServiceUser $item, ServiceUserModel $model): ServiceUserModel
	{
		$model
			->setUserId($item->userId)
			->setUid($item->uid)
		;

		return $model;
	}
}
