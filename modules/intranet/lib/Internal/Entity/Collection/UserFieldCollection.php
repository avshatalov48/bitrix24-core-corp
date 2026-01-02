<?php

namespace Bitrix\Intranet\Internal\Entity\Collection;

use Bitrix\Intranet\Internal\Entity\UserField\UserField;
use Bitrix\Main\Entity\EntityCollection;
use Bitrix\Main\Entity\EntityInterface;

class UserFieldCollection extends EntityCollection
{
	protected static function getEntityClass(): string
	{
		return UserField::class;
	}

	/**
	 * @return UserField|null
	 */
	public function findById(string $id): ?EntityInterface
	{
		return $this->find(fn (UserField $userField) => $userField->getId() === $id);
	}
}
