<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ServiceIsNotEnoughException extends ServiceException
{
	protected $code = 9721;
	public const SYMBOLIC_CODE = 'not_enough';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_DOES_NOT_HAVE_UNITS')
			?? 'Client does not have enough units';
	}
}
