<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Command\Structure\Node\Handler;

use Bitrix\HumanResources\Command\Structure\Node\SaveNodeSettingsCommand;
use Bitrix\HumanResources\Command\Structure\Node\CreateNodeCommand;
use Bitrix\HumanResources\Internals;
use Bitrix\Main\Result;

class SaveNodeSettingsHandler
{
	private Internals\Service\Structure\NodeSettingsService $nodeSettingsService;

	public function __construct()
	{
		$this->nodeSettingsService = Internals\Service\Container::getNodeSettingsService();
	}

	public function __invoke(SaveNodeSettingsCommand $command): Result
	{
		return $this->nodeSettingsService->save($command->node->id, $command->settings);
	}
}
