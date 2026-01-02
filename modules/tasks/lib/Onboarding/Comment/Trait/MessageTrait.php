<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Comment\Trait;

use Bitrix\Main\Localization\Loc;

trait MessageTrait
{
	private function loadPosterMessages(): void
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/lib/comments/task/commentposter.php');
	}
}