<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientHostKeyIsNotRecognizedException extends ClientException
{
	protected $code = 9712;
	public const SYMBOLIC_CODE = 'host_key_is_not_recognized';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_HOST_KEY_IS_NOT_RECOGNIZED')
			?? 'Client is not recognized on the BaasController.';
	}
}
