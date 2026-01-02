<?php
namespace Bitrix\ImMobile\Controller;

use Bitrix\Main\Engine\CurrentUser;

class Messenger extends Tab
{
	/**
	 * @restMethod immobile.Messenger.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser, $options = []): array
	{
		return parent::loadAction($methodList, $currentUser, $options);
	}

	protected function getRecentList(): array
	{
		return [];
	}
}