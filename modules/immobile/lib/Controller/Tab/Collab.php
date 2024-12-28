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
	public function loadAction(array $methodList, CurrentUser $currentUser): array
	{
		return parent::loadAction($methodList, $currentUser);
	}

	protected function getRecentList(): array
	{
		$recentList = RecentCollab::getCollabs(self::LIMIT);

		return $this->toRestFormatWithPaginationData(
			[$recentList],
			self::LIMIT,
			$recentList->count()
		);
	}
}
