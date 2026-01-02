<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientIsNotRecognizedException extends ClientException
{
	protected $code = 9711;
	public const SYMBOLIC_CODE = 'is_not_recognized';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_IS_NOT_RECOGNIZED')
			?? 'Client is not recognized on the BaasController.';
	}
}
