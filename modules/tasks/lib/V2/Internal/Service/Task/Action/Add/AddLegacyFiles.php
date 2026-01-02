<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\LegacyFileTrait;

class AddLegacyFiles
{
	use ConfigTrait;
	use LegacyFileTrait;

	public function __invoke(array $fields): void
	{
		$this->addFiles($fields, $fields['ID'], $this->config->getUserId(), $this->config->isCheckFileRights());
	}
}