<?php

namespace Bitrix\Sign\Service\Providers;

use Bitrix\Sign\Type\ProviderCode;

final class ProviderVisibilityService
{
	/**
	 * Get provider codes that should be hidden from UI.
	 *
	 * @return array<string> Array of provider constants
	 */
	public function getHiddenProviderCodes(): array
	{
		return [
			ProviderCode::SES_RU_EXPRESS,
		];
	}

	/**
	 * Get hidden providers in string format (kebab-case).
	 *
	 * @return array<string>
	 */
	public function getHiddenProviders(): array
	{
		return array_map(
			[ProviderCode::class, 'toRepresentativeString'],
			$this->getHiddenProviderCodes()
		);
	}

	/**
	 * Check if provider should be hidden from UI.
	 */
	public function isProviderHidden(string $providerCode): bool
	{
		return in_array($providerCode, $this->getHiddenProviders(), true);
	}
}
