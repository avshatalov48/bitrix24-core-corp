<?php

namespace Bitrix\Crm\Integration\Catalog\Access\Agent;

use Bitrix\Main\Loader;

/**
 * An agent that adds a `catalog` agent if the module is installed.
 *
 * It is necessary for situations when the `crm` module is installed, `catalog` is not.
 */
class InstallPermissionsAgent
{
	/**
	 * Execute agent.
	 *
	 * @return string
	 */
	public static function runAgent(): string
	{
		$self = new static();
		$isSuccess = $self->run();

		if ($isSuccess)
		{
			return '';
		}

		return __METHOD__ . '();';
	}

	/**
	 * Execute agent.
	 *
	 * @return bool TRUE - if the process successes.
	 */
	private function run(): bool
	{
		if (Loader::includeModule('catalog'))
		{
			\Bitrix\Catalog\Access\Install\AccessInstaller::installByAgent();

			return true;
		}

		return false;
	}
}
