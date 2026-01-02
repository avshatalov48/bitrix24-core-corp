<?php

namespace Bitrix\Intranet\Command;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Config\Option;

class AttachJwtTokenToUrlCommand
{
	public function __construct(
		private Uri $uri,
		private string $token,
		private string $parametrName = 'invite_token'
	)
	{}

	private static function getUri(): Uri
	{
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

		if (!Loader::includeModule('bitrix24'))
		{
			$uri->setPath('/auth/registration_link.php');
			$uri->addParams(['register' => 'yes']);
		}

		return $uri;
	}

	static function createDefaultInstance(string $token): self
	{
		$uri = self::getUri();

		return new AttachJwtTokenToUrlCommand($uri, $token, 'invite_token');
	}

	static function createInstanceWithUserLang(string $token, string $userLang = LANGUAGE_ID): self
	{
		$uri = self::getUri();
		$uri->addParams(['user_lang' => $userLang]);

		return new AttachJwtTokenToUrlCommand($uri, $token, 'invite_token');
	}

	public function attach(): Uri
	{
		return $this->uri->addParams([
			$this->parametrName => $this->token,
		]);
	}
}
