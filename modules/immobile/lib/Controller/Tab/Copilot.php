<?php

namespace Bitrix\ImMobile\Controller\Tab;

use Bitrix\ImMobile\Controller\Tab;
use Bitrix\Main\Engine\CurrentUser;

class Copilot extends Tab
{
	/**
	 * @restMethod immobile.Tab.Copilot.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser, $options = []): array
	{
		return parent::loadAction($methodList, $currentUser, $options);
	}

	protected function getRecentList(): array
	{
		return static::getCopilotList();
	}
}
