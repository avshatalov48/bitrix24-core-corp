<?php

namespace Bitrix\Sign\Item;

/**
 * @extends Collection<ProviderDate>
 */
class ProviderDateCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return ProviderDate::class;
	}

	public function getLastUsedByUid(string $companyUid): ?ProviderDate
	{
		return $this->filter(
			static fn (ProviderDate $providerDate): bool => $providerDate->companyUid === $companyUid,
		)->sortByRule(
			static fn($a, $b) => $b->dateLastUsed <=> $a->dateLastUsed,
		)->getFirst();
	}

	public function getLastUsedByCompanyId(int $companyId): ?ProviderDate
	{
		return $this->filter(
			static fn (ProviderDate $providerDate): bool => $providerDate->companyId === $companyId,
		)->sortByRule(
			static fn($a, $b) => $b->dateLastUsed <=> $a->dateLastUsed,
		)->getFirst();
	}
}

