<?php

namespace Bitrix\Crm\Router;

use Bitrix\Main\Application;
use Bitrix\Main\Composite\Engine;
use CHTTP;

final class ResponseHelper
{
	public static function showPageNotFound(): never
	{
		if (!defined('ERROR_404'))
		{
			define('ERROR_404', 'Y');
		}

		CHTTP::setStatus('404 Not Found');

		global $APPLICATION;
		if ($APPLICATION->RestartWorkarea())
		{
			if (!defined('BX_URLREWRITE'))
			{
				define('BX_URLREWRITE', true);
			}

			Engine::setEnable(false);

			require Application::getDocumentRoot() . '/404.php';
		}

		die();
	}
}
