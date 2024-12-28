<?php

namespace Bitrix\Sign\Item;

/**
 * @extends Collection<User>
 */
class UserCollection extends Collection
{
	/**
	 * @var array<int, User>
	 */
	private array $idMap;

	protected function getItemClassName(): string
	{
		return User::class;
	}

	public function getByIdMap(int $id): ?User
	{
		if (!isset($this->idMap))
		{
			$this->initIdMap();
		}

		return $this->idMap[$id] ?? null;
	}

	private function initIdMap(): void
	{
		$this->idMap = [];
		foreach ($this as $user)
		{
			if ($user instanceof User && $user->id)
			{
				$this->idMap[$user->id] = $user;
			}
		}
	}
}
