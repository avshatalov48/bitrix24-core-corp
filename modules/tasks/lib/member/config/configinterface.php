<?php

namespace Bitrix\Tasks\Member\Config;

interface ConfigInterface
{
	public function getType(): string;

	/** @return ConfigInterface[] */
	public function getCoveringConfigs(): array;
	public function escapeString(): bool;
}