<?php

namespace Bitrix\Tasks\Member\Config;

class WorkConfig implements ConfigInterface
{
	public function getType(): string
	{
		return 'work';
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