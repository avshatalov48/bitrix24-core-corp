<?php

namespace Bitrix\Intranet\Command;

use Bitrix\Main\Context;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\JWT;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Config\Option;

class AttachJwtTokenToUrlCommand
{
	public function __construct(
		private string $secret,
		private Uri $uri,
		private string $parametrName = 'invite_token'
	)
	{}

	public function attach(mixed $payload): Uri
	{
		return $this->uri->addParams([
			$this->parametrName => JWT::encode($payload, $this->secret),
		]);
	}
}