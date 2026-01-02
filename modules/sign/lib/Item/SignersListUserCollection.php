<?php

namespace Bitrix\Sign\Item;

/**
 * @extends Collection<SignersListUser>
 */
class SignersListUserCollection extends Collection
{
	/**
	 * @return list<?int>
	 */
	public function getIds(): array
	{
		return array_map(
			static fn(SignersListUser $user): int => $user->id,
			$this->toArray(),
		);
	}

	public function getUserIds(): array
	{
		return array_map(
			static fn(SignersListUser $user): int => $user->userId,
			$this->toArray(),
		);
	}

	public function getListIds(): array
	{
		return array_map(
			static fn(SignersListUser $user): int => $user->listId,
			$this->toArray(),
		);
	}

	public function findByUserId(int $id): ?SignersListUser
	{
		return $this->findByRule(static fn(SignersListUser $user): bool => $user->userId === $id);
	}

	protected function getItemClassName(): string
	{
		return SignersListUser::class;
	}
}
