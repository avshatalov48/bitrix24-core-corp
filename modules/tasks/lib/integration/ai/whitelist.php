<?php

namespace Bitrix\Tasks\Integration\AI;

class WhiteList
{
	public function getAvailableHosts(): array
	{
		return [
			'ai-bitrix.hb.bizmrg.com',
		];
	}

	public function getAvailableSchemes(): array
	{
		return [
			'https',
		];
	}

	public function isHostAvailable(string $host): bool
	{
		return in_array($host, $this->getAvailableHosts(), true);
	}

	public function isSchemeAvailable(string $scheme): bool
	{
		return in_array($scheme, $this->getAvailableSchemes(), true);
	}
}