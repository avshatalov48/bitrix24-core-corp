<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\UrlManager;
use COption;

class UrlService
{
	public function getHostUrl(string $serverName = ''): string
	{
		if (!empty($serverName))
		{
			return $this->getCustomServerUrl($serverName);
		}

		return UrlManager::getInstance()->getHostUrl();
	}

	public function getHost(): string
	{
		if (defined('BX24_HOST_NAME') && !empty(BX24_HOST_NAME))
		{
			return BX24_HOST_NAME;
		}

		if (defined('SITE_SERVER_NAME') && !empty(SITE_SERVER_NAME))
		{
			return SITE_SERVER_NAME;
		}

		return Option::get('main', 'server_name');
	}

	// compatability with old code
	private function getCustomServerUrl(string $name): string
	{
		if (!$name)
		{
			if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME)
			{
				$name = SITE_SERVER_NAME;
			}
			else
			{
				$name = COption::GetOptionString("main", "server_name", $_SERVER['HTTP_HOST']);
			}
		}

		if (
			(mb_stripos($name, 'https://') !== 0)
			&& (mb_stripos($name, 'http://') !== 0)
		)
		{
			if (Context::getCurrent()?->getRequest()->isHttps())
			{
				$name = 'https://' . $name;
			}
			else
			{
				$name = 'http://' . $name;
			}
		}

		$protocols = str_replace(
			['http://', 'https://', 'HTTP://', 'HTTPS://'],
			['', '', '', ''],
			$name
		);

		$slashPos = mb_strpos($protocols, '/');
		if ($slashPos >= 1)
		{
			$length = $slashPos;
			$protocols = mb_substr(0, $length);
		}

		$isServerPortAlreadyGiven = false;
		if (str_contains($protocols, ':'))
		{
			$isServerPortAlreadyGiven = true;
		}

		$port = '';

		if (
			!$isServerPortAlreadyGiven
			&& isset($_SERVER['SERVER_PORT'])
			&& !empty($_SERVER['SERVER_PORT'])
			&& ((int)$_SERVER['SERVER_PORT'] !== 80)
			&& ((int)$_SERVER['SERVER_PORT'] !== 443)
		)
		{
			$port = ':' . $_SERVER['SERVER_PORT'];
		}

		if (!$isServerPortAlreadyGiven)
		{
			$name .= $port;
		}

		return $name;
	}
}
