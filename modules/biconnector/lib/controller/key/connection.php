<?php

namespace Bitrix\BIConnector\Controller\Key;

use Bitrix\BIConnector\Manager;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Rest\Engine\ActionFilter\AuthType;

/**
 * Class Connection
 * @package Bitrix\BIConnector\Controller\Key
 */
class Connection extends Controller
{
	/**
	 * Returns list of connection.
	 *
	 * @param \CRestServer $server Main rest response object.
	 *
	 * @return array
	 */
	public function listAction(\CRestServer $server)
	{
		$result = [];
		$connections = Manager::getInstance()->getConnections();
		foreach ($connections as $item)
		{
			$result[] = $item;
		}

		return $result;
	}

	/**
	 * Returns array of rest filters.
	 *
	 * @return array
	 */
	public function getDefaultPreFilters()
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Scope(ActionFilter\Scope::REST),
			new AuthType(AuthType::APPLICATION),
		];
	}
}
