<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Contract;

interface AgentInstaller
{
	/**
	 * @return string
	 * @throws \Exception
	 */
	public function install(): string;
}