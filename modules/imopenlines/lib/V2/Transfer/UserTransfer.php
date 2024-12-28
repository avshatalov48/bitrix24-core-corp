<?php

namespace Bitrix\ImOpenLines\V2\Transfer;

use Bitrix\Im\V2\Entity\User\User;

class UserTransfer extends TransferItem
{
	protected User $entity;

	public function __construct(User $entity)
	{
		$this->entity = $entity;
	}

	public function getId(): ?int
	{
		return $this->entity->getId();
	}

	public function getTransferId(): int
	{
		return $this->getId();
	}

	public static function getInstance(mixed $id): ?self
	{
		$user = User::getInstance($id);

		if (!$user->isExist())
		{
			return null;
		}

		return new UserTransfer($user);
	}
}