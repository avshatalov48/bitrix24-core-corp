<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use Bitrix\Main\Localization\Loc;

trait LocalizationTrait
{
	protected function loadMessages(): void
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/lib/control/task.php');
	}
}