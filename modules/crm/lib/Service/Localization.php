<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Localization\Loc;

class Localization
{
	public function loadMessages(): array
	{
		return Loc::loadLanguageFile(__FILE__);
	}

	public function loadKanbanMessages(): array
	{
		return Loc::loadLanguageFile(__DIR__ . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR .  '..' . DIRECTORY_SEPARATOR . 'kanban.php');
	}
}
