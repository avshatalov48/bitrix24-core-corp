<?php

namespace Bitrix\Extranet\Entity\Collection;

use Bitrix\Extranet\Entity\ExtranetUser;
use Bitrix\Main\ArgumentException;
use Bitrix\Extranet\Enum;

/**
 * @extends BaseCollection<ExtranetUser>
 */
class ExtranetUserCollection extends BaseCollection
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return ExtranetUser::class;
	}

	/**
	 * @return BaseCollection<ExtranetUser>
	 * @throws ArgumentException
	 */
	public function filterByRole(Enum\User\ExtranetRole $role): BaseCollection
	{
		return $this->filter(fn(ExtranetUser $user) => $user->getRole() === $role);
	}

	public function getIds(): array
	{
		return $this->map(fn(ExtranetUser $user) => $user->getId());
	}
}
