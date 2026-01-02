<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Command;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Service\CrmItemService;

class AddItemsHandler
{
	public function __construct(
		private readonly CrmItemService $itemService,
	)
	{

	}

	public function __invoke(AddItemsCommand $command): void
	{
		$this->itemService->add(
			taskId: $command->taskId,
			userId: $command->userId,
			crmItemIds: $command->crmItemIds,
		);
	}
}
