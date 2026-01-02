<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ServiceException extends ExceptionFromError
{
	protected $code = 9730;

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_SERVICE_PROBLEM')
			?? 'Service is not available for the client';
	}
}
