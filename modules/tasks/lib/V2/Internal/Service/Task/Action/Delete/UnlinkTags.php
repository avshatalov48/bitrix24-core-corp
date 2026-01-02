<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Control\Tag;

class UnlinkTags
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		$tagService = new Tag($this->config->getUserId());

		$tagService->unlinkTags((int)$fullTaskData['ID']);
	}
}