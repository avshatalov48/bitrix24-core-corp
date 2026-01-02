<?php

namespace Bitrix\Crm\Integration\AI\ContextCollector;

use Bitrix\Crm\Integration\AI\Contract\ContextCollector;

final class CollectionCollector implements ContextCollector
{
	public function __construct(
		/** @var array<string, $collector> */
		private readonly array $collectors,
	)
	{
	}

	public function collect(): array
	{
		$result = [];
		foreach ($this->collectors as $name => $collector)
		{
			$result[$name] = $collector->collect();
		}

		return $result;
	}
}
