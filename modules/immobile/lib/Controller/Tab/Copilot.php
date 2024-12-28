<?php

namespace Bitrix\ImMobile\Controller\Tab;

use Bitrix\ImMobile\Controller\Tab;
use Bitrix\Main\Engine\CurrentUser;

class Copilot extends Tab
{
	/**
	 * @restMethod immobile.Tab.Copilot.load
	 */
	public function loadAction(array $methodList, CurrentUser $currentUser): array
	{
		return parent::loadAction($methodList, $currentUser);
	}

	protected function getRecentList(): array
	{
		$recentList = \Bitrix\Im\Recent::getList(
			null,
			[
				'JSON' => 'Y',
				'SKIP_OPENLINES' => 'Y',
				'GET_ORIGINAL_TEXT' => 'N',
				'OFFSET' => self::OFFSET,
				'LIMIT' => self::LIMIT,
				'ONLY_COPILOT' => 'Y',
			]
		);

		return $recentList ?: [];
	}
}
