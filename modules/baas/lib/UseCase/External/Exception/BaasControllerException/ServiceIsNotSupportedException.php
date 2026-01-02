<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ServiceIsNotSupportedException extends ServiceException
{
	protected $code = 9722;
	public const SYMBOLIC_CODE = 'not_supported';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_DOES_NOT_HAVE_SUCH_SERVICE')
			?? 'Client does not have such service';
	}
}
