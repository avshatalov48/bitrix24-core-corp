<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Sign\Engine\Controller;
use Bitrix\Main;
use Bitrix\Sign\Service;

class Counters extends Controller
{
	public function getCurrentCountDocumentAction(): array
	{
		$count = 0;
		$currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		if ($currentUserId !== null)
		{
			$count = \Bitrix\Sign\Service\Container::instance()
				->getMemberRepository()
				->getCountForCurrentUserAction($currentUserId)
			;
		}

		return compact('count');
	}
}
