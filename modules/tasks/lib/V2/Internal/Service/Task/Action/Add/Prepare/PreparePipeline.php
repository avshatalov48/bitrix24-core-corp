<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;

class PreparePipeline
{
	public function __construct(
		private readonly AddConfig $config,
		private readonly array $preparersClasses
	)
	{

	}

	public function __invoke(array $fields): array
	{
		foreach ($this->preparersClasses as $class)
		{
			if (!is_subclass_of($class, PrepareFieldInterface::class))
			{
				continue;
			}

			$fields = (new $class($this->config))($fields);
		}

		return $fields;
	}
}