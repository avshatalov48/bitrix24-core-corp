<?php

namespace Bitrix\ImConnectorMobile;

class Workspace
{
	/**
	 * Returns the path from the root directory to mobile messenger connectors workspace (imconnectormobile)
	 *
	 * @return string
	 */
	public static function getPath(): string
	{
		return '/bitrix/mobileapp/imconnectormobile/';
	}
}
