<?php

namespace Bitrix\Call\Controller;

use Bitrix\Call\Settings;
use Bitrix\Call\Error;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Web\JWT;


abstract class JwtController extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new HttpMethod([HttpMethod::METHOD_POST]),
			new Csrf(false),
			new CloseSession(),
		];
	}

	protected function decodeJwtParameter(): \Closure
	{
		return function ($className, string $jwt)
		{
			try
			{
				$payload = JWT::decode($jwt, base64_decode(Settings::getPrivateKey()), ['HS256']);

				return new $className($payload);
			}
			catch (\Exception $e)
			{
				$this->addError(new Error(Error::WRONG_JWT));

				return null;
			}
		};
	}
}