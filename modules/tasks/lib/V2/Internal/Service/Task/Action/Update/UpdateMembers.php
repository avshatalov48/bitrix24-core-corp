<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Control\Member;

class UpdateMembers
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData, array $changes): void
	{
		$members = new Member($this->config->getUserId(), (int)$fullTaskData['ID']);
		$members->set($fields, $changes);
	}
}