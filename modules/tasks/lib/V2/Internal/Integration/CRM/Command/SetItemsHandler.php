<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Command;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Service\CrmItemService;

class SetItemsHandler
{
	public function __construct(
		private readonly CrmItemService $itemService,
	)
	{

	}

	public function __invoke(SetItemsCommand $command): void
	{
		$this->itemService->set(
			taskId: $command->taskId,
			userId: $command->userId,
			crmItemIds: $command->crmItemIds,
		);
	}
}
