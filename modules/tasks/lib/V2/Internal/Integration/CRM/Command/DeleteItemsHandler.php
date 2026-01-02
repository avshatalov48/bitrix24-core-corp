<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Command;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Service\CrmItemService;

class DeleteItemsHandler
{
	public function __construct(
		private readonly CrmItemService $itemService,
	)
	{

	}

	public function __invoke(DeleteItemsCommand $command): void
	{
		$this->itemService->delete(
			taskId: $command->taskId,
			userId: $command->userId,
			crmItemIds: $command->crmItemIds,
		);
	}
}
