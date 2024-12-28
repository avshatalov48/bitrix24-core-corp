<?php

namespace Bitrix\ImMobile\Controller\Tab;

use Bitrix\Im\V2\Recent\RecentChannel;
use Bitrix\ImMobile\Controller\Tab;
use Bitrix\Main\Engine\CurrentUser;

class Channel extends Tab
{
	/**
	 * @restMethod immobile.Tab.Channel.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser): array
	{
		return parent::loadAction($methodList, $currentUser);
	}

	protected function getRecentList(): array
	{
		$recentList = RecentChannel::getOpenChannels(self::LIMIT);

		return $this->toRestFormatWithPaginationData(
			[$recentList],
			self::LIMIT,
			$recentList->count()
		);
	}
}
