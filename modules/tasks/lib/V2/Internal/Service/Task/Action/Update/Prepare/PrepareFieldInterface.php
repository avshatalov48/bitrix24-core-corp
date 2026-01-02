<?php

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

interface PrepareFieldInterface
{
	public function __construct(UpdateConfig $config);

	public function __invoke(array $fields, array $fullTaskData): array;
}