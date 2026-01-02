<?php

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;

interface PrepareFieldInterface
{
	public function __construct(AddConfig $config);

	public function __invoke(array $fields): array;
}