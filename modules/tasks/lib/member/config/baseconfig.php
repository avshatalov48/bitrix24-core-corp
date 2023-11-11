<?php

namespace Bitrix\Tasks\Member\Config;

class BaseConfig implements Config
{
	public function getType(): string
	{
		return 'base';
	}

	public function getCoveringConfigs(): array
	{
		return [
			new AdditionalConfig(),
		];
	}

	public function escapeString(): bool
	{
		return true;
	}
}