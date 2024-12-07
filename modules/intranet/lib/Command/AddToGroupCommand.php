<?php

namespace Bitrix\Intranet\Command;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;

class AddToGroupCommand
{
	public function __construct(
		private int $groupId,
		private UserCollection $users
	)
	{}

	public function execute()
	{
		\CSocNetUserToGroup::addUniqueUsersToGroup($this->groupId, $this->users->map(fn(User $user) => $user->getId()));
	}
}