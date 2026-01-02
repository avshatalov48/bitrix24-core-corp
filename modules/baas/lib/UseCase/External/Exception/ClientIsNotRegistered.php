<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception;

use \Bitrix\Main;

class ClientIsNotRegistered extends UseCaseException
{
	protected $code = 8711;

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_IS_NOT_REGISTERED')
			?? 'Client has not registered yet.';
	}
}
