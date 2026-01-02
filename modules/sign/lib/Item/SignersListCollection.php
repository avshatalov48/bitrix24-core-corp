<?php

namespace Bitrix\Sign\Item;

/**
 * @extends Collection<SignersList>
 */
class SignersListCollection extends Collection
{
	/**
	 * @return list<?int>
	 */
	public function getIds(): array
	{
		return array_map(
			static fn(SignersList $list): int => $list->id,
			$this->toArray(),
		);
	}

	public function findById(int $id): ?SignersList
	{
		return $this->findByRule(static fn(SignersList $list): bool => $list->id === $id);
	}

	protected function getItemClassName(): string
	{
		return SignersList::class;
	}
}
