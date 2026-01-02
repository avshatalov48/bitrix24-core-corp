<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class UnknownException extends ExceptionFromError
{
	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_ON_THE_CONTROLLER')
			?? 'There is some error on the BaasController.';
	}
}
