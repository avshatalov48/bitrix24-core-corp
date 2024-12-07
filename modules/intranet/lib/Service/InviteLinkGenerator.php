<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Uri;
use Bitrix\Intranet\Command\AttachJwtTokenToUrlCommand;

class InviteLinkGenerator
{
	public function __construct(
		private AttachJwtTokenToUrlCommand $jwtTokenUrlService,
		private string $secret,
	)
	{}

	public static function createInstance(): ?self
	{
		$secret = Option::get("socialservices", "new_user_registration_secret", null);
		if ($secret === null)
		{
			return null;
		}

		$serverName = Option::get('main', 'server_name');

		if (defined('BX24_HOST_NAME') && !empty(BX24_HOST_NAME))
		{
			$serverName = BX24_HOST_NAME;
		}
		else if (defined('SITE_SERVER_NAME') && !empty(SITE_SERVER_NAME))
		{
			$serverName = SITE_SERVER_NAME;
		}

		$baseUrl = (Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://') . $serverName;
		$uri = new Uri($baseUrl);

		return new self(new AttachJwtTokenToUrlCommand((string)$secret, $uri), $secret);
	}

	private function create(int $collabId): Uri
	{
		$payload = [
			'collab_id' => $collabId,
		];

		$uri = $this->jwtTokenUrlService->attach($payload);

		return $uri;
	}

	public function getCollabLink(int $collabId): string
	{
		$uri = $this->create($collabId);

		return $uri->getUri();
	}

	public function getShortCollabLink(int $collabId): string
	{
		$uri = $this->create($collabId);

		return $uri->getScheme().'://'.$uri->getHost().\CBXShortUri::GetShortUri($uri->getUri());
	}
}
