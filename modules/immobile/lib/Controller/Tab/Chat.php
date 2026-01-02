<?php

namespace Bitrix\ImMobile\Controller\Tab;

use Bitrix\ImMobile\Controller\Tab;
use Bitrix\Main\Engine\CurrentUser;


class Chat extends Tab
{
	/**
	 * @restMethod immobile.Tab.Chat.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser, $options = []): array
	{
		return parent::loadAction($methodList, $currentUser, $options);
	}

	protected function getRecentList(): array
	{
		return static::getChatsList();
	}
}
