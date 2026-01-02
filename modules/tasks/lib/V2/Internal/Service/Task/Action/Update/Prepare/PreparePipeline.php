<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareFieldInterface;

class PreparePipeline
{
	public function __construct(
		private readonly UpdateConfig $config,
		private readonly array $preparersClasses
	)
	{

	}

	public function __invoke(array $fields, array $fullTaskData): array
	{
		foreach ($this->preparersClasses as $class)
		{
			if (!is_subclass_of($class, PrepareFieldInterface::class))
			{
				continue;
			}

			$fields = (new $class($this->config))($fields, $fullTaskData);
		}

		return $fields;
	}
}