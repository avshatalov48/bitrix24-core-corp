<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareIntegration implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$fields['IM_CHAT_ID'] = (int)($fields['IM_CHAT_ID'] ?? 0);
		$fields['IM_MESSAGE_ID'] = (int)($fields['IM_MESSAGE_ID'] ?? 0);

		return $fields;
	}
}