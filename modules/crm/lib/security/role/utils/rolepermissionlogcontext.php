<?php

namespace Bitrix\Crm\Security\Role\Utils;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Crm\Service\Container;

class RolePermissionLogContext
{
	use Singleton;

	private array $context = [];
	private bool $isOrmEventsLogEnabled = true;

	public function get(): array
	{
		return $this->context;
	}

	public function clear(): void
	{
		$this->context = [];
	}


	public function set(array $context): void
	{
		$this->context = $context;
	}

	public function enableOrmEventsLog(): void
	{
		$this->isOrmEventsLogEnabled = true;
	}

	public function disableOrmEventsLog(): void
	{
		$this->isOrmEventsLogEnabled = false;
	}

	public function isOrmEventsLogEnabled(): bool
	{
		return $this->isOrmEventsLogEnabled;
	}

	public function appendTo(array $logParams): array
	{
		$context = $this->get();
		if (empty($context)) // Some unexpected permission changes scenario. Trace logged to know the reason of change.
		{
			$context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}
		$context['userId'] = Container::getInstance()->getContext()->getUserId();

		return array_merge(
			$context,
			$logParams
		);
	}
}
