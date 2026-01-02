<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientHostNameIsNotRecognizedException extends ClientException
{
	protected $code = 9712;
	public const SYMBOLIC_CODE = 'host_name_is_not_recognized';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_HOST_NAME_IS_NOT_RECOGNIZED')
			?? 'Client with such name is not recognized on the BaasController.';
	}
}
