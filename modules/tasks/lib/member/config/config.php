<?php

namespace Bitrix\Tasks\Member\Config;

interface Config
{
	public function getType(): string;

	/** @return Config[] */
	public function getCoveringConfigs(): array;
	public function escapeString(): bool;
}