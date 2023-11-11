<?php

namespace Bitrix\Tasks\Member\Config;

class AdditionalConfig implements Config
{
	public function getType(): string
	{
		return 'additional';
	}

	public function getCoveringConfigs(): array
	{
		return [];
	}

	public function escapeString(): bool
	{
		return true;
	}
}