<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParseTextTrait;
use Bitrix\Tasks\Control\Tag;

class AddTags
{
	use ConfigTrait;
	use ParseTextTrait;

	public function __invoke(array $fields): void
	{
		$parsedTags = $this->parseTags($fields);
		if (
			empty($parsedTags)
			&& !array_key_exists('TAGS', $fields)
		)
		{
			return;
		}

		$groupId = (int)($fields['GROUP_ID'] ?? 0);

		(new Tag($this->config->getUserId()))->add($fields['ID'], $parsedTags, $groupId);
	}
}