<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParseTextTrait;
use Bitrix\Tasks\Control\Tag;

class UpdateTags
{
	use ConfigTrait;
	use ParseTextTrait;

	public function __invoke(array $fields, array $fullTaskData, array $changes): void
	{
		$parsedTags = $this->parseTags($fields);
		if (
			empty($parsedTags)
			&& !array_key_exists('TAGS', $fields)
		)
		{
			return;
		}
		$oldGroupId = 0;
		$newGroupId = 0;
		if ($changes && array_key_exists('GROUP_ID', $changes))
		{
			$oldGroupId = (int)$changes['GROUP_ID']['FROM_VALUE'];
			$newGroupId = (int)$changes['GROUP_ID']['TO_VALUE'];
		}

		$tag = new Tag($this->config->getUserId());
		$tag->set((int)$fullTaskData['ID'], $parsedTags, $oldGroupId, $newGroupId);
		Container::getInstance()->getTaskTagRepository()->invalidate((int)$fullTaskData['ID']);
	}
}
