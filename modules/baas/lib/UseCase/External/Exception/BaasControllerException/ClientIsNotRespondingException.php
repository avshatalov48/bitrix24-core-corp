<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientIsNotRespondingException extends ClientException
{
	protected $code = 9713;
	public const SYMBOLIC_CODE = 'is_not_responding';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_IS_NOT_RESPONDING')
			?? 'Client is not responding to the BaasController.';
	}
}
