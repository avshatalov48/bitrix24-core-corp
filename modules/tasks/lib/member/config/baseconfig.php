<?php

namespace Bitrix\Tasks\Member\Config;

class BaseConfig implements ConfigInterface
{
	public function getType(): string
	{
		return 'base';
	}

	public function getCoveringConfigs(): array
	{
		return [
			new AdditionalConfig(),
			new WorkConfig(),
		];
	}

	public function escapeString(): bool
	{
		return true;
	}
}