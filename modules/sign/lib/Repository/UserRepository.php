<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\EO_User;
use Bitrix\Main\UserTable;
use Bitrix\Sign\Item\User;
use Bitrix\Sign\Item\UserCollection;

final class UserRepository
{
	private const SELECTED_FIELDS = [
		'ID',
		'NAME',
		'LAST_NAME',
		'SECOND_NAME',
		'PERSONAL_PHOTO',
		'NOTIFICATION_LANGUAGE_ID',
		'ACTIVE',
	];

	public function getById(int $userId): ?User
	{
		$model = UserTable::query()
			->setSelect(self::SELECTED_FIELDS)
			->where('ID', $userId)
			->fetchObject()
		;

		return ($model !== null) ? $this->extractItemFromModel($model) : null;
	}

	private function extractItemFromModel(EO_User $model): User
	{
		return new User(
			id: $model->getId(),
			name: $model->getName(),
			lastName: $model->getLastName(),
			secondName: $model->getSecondName(),
			personalPhotoId: $model->getPersonalPhoto(),
			notificationLanguageId: $model->getNotificationLanguageId(),
			isActive: $model->getActive(),
		);
	}

	/**
	 * @param list<int> $ids
	 *
	 * @return UserCollection
	 */
	public function getByIds(array $ids): UserCollection
	{
		if (empty($ids))
		{
			return new UserCollection();
		}

		$models = UserTable::query()
			->setSelect(self::SELECTED_FIELDS)
			->whereIn('ID', $ids)
			->fetchCollection()
		;

		$items = new UserCollection();
		foreach ($models as $model)
		{
			$items->add($this->extractItemFromModel($model));
		}

		return $items;
	}

}
