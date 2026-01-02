<?php

namespace Bitrix\ImMobile\Controller\Tab;

use Bitrix\Im\V2\Recent\RecentCollab;
use Bitrix\ImMobile\Controller\Tab;
use Bitrix\Main\Engine\CurrentUser;

class Collab extends Tab
{
	/**
	 * @restMethod immobile.Tab.Collab.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser, $options = []): array
	{
		return parent::loadAction($methodList, $currentUser, $options);
	}

	protected function getRecentList(): array
	{
		return static::getCollabList();
	}
}
