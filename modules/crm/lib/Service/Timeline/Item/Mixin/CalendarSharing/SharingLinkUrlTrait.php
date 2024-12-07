<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Main\Web\Uri;

trait SharingLinkUrlTrait
{
	public function getSharingLinkUrl(?string $linkHash): ?Uri
	{
		$result = null;

		if ($linkHash)
		{
			$result = new Uri($this->getSharingLinkTextUrl($linkHash));
		}

		return $result;
	}

	private function getSharingLinkTextUrl(string $linkHash): string
	{
		$sharingPublicPath = '/pub/calendar-sharing/';
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
		$server = $context->getServer();
		$domain = $server->getServerName() ?: \COption::getOptionString('main', 'server_name', '');

		if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
		{
			$domain = $matches['domain'];
			$port = (int)$matches['port'];
		}
		else
		{
			$port = (int)$server->getServerPort();
		}

		$port = in_array($port, [80, 443], true) ? '' : ':'.$port;
		$serverPath = $scheme . '://' . $domain . $port;
		$url = $serverPath . $sharingPublicPath . $linkHash . '/';

		return $serverPath . \CBXShortUri::getShortUri($url);
	}
}