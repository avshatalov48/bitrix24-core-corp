<?php

namespace Bitrix\ImOpenLines\V2\Controller;

use Bitrix\Im;
use Bitrix\Main\Loader;

Loader::requireModule('im');

abstract class BaseController extends Im\V2\Controller\BaseController
{
	protected function getDefaultPreFilters()
	{
		return array_merge(
			[
				new \Bitrix\Intranet\ActionFilter\IntranetUser(),
			],
			parent::getDefaultPreFilters(),
		);
	}
}