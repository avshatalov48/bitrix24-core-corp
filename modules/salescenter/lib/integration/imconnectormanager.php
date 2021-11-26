<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\ImConnector;

class ImConnectorManager extends Base
{
	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'imconnector';
	}


	public function isNotificationsEnabled()
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		$notifications = new ImConnector\Tools\Connectors\Notifications();
		return $notifications->isEnabled();
	}
}