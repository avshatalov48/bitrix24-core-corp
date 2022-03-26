<?php


namespace Bitrix\Crm\Controller;

use Bitrix\Main\Engine\Action;
use Bitrix\Rest\Integration\CrmViewManager;
use Bitrix\Rest\Integration\Controller\Base;

class Controller extends Base
{
	protected function createViewManager(Action $action): CrmViewManager
	{
		return new CrmViewManager($action);
	}

	protected static function getApplication()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		return $APPLICATION;
	}

	protected static function getGlobalUser()
	{
		/** @global \CUser $USER */
		global $USER;

		return $USER;
	}

	protected static function getNavData($start, $orm = false)
	{
		if($start >= 0)
		{
			return ($orm ?
				['limit' => \IRestService::LIST_LIMIT, 'offset' => intval($start)]
				:['nPageSize' => \IRestService::LIST_LIMIT, 'iNumPage' => intval($start / \IRestService::LIST_LIMIT) + 1]
			);
		}
		else
		{
			return ($orm ?
				['limit' => \IRestService::LIST_LIMIT]
				:['nTopCount' => \IRestService::LIST_LIMIT]
			);
		}
	}
}