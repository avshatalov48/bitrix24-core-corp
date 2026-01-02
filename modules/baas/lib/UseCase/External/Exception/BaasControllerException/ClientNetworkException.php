<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientNetworkException extends ClientException
{
	protected $code = 9770;
	public const SYMBOLIC_CODE = 'NETWORK';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_NETWORK')
			?? 'Network error. Boosts server unreachable. Check the address or net';
	}
}