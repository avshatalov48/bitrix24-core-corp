<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\CheckList;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service;

class CollapseCheckListCommandHandler
{
	public function __construct(
		private readonly Service\CheckList\UserOptionService $userOptionService,
	)
	{}

	public function __invoke(CollapseCheckListCommand $command): void
	{
		$this->userOptionService->delete(new Entity\CheckList\UserOption(
			userId: $command->userId,
			itemId: $command->checkListId,
			code: Entity\CheckList\Option::EXPANDED,
		));

		$this->userOptionService->add(new Entity\CheckList\UserOption(
			userId: $command->userId,
			itemId: $command->checkListId,
			code: Entity\CheckList\Option::COLLAPSED,
		));
	}
}
