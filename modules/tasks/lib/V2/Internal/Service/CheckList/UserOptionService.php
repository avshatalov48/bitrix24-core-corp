<?php

namespace Bitrix\Tasks\V2\Internal\Service\CheckList;

use Bitrix\Tasks\V2\Internal\Repository\CheckListUserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Entity;

class UserOptionService
{
	public function __construct(
		private readonly CheckListUserOptionRepositoryInterface $checkListUserOptionRepository,
	)
	{}

	public function add(Entity\CheckList\UserOption $userOption): void
	{
		$this->checkListUserOptionRepository->add($userOption);
	}

	public function delete(Entity\CheckList\UserOption $userOption): void
	{
		$this->checkListUserOptionRepository->delete($userOption->userId, $userOption->itemId, [$userOption->code]);
	}
}
