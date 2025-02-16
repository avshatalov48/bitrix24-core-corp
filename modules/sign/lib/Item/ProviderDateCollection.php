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

	public function getByUid(string $companyUid): ?ProviderDate
	{
		return $this->findByRule(
			static fn(ProviderDate $providerDate): bool => $providerDate->companyUid === $companyUid
		);
	}
}
