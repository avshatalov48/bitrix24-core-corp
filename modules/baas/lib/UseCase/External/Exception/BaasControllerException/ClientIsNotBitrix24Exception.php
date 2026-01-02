<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientIsNotBitrix24Exception extends ClientException
{
	protected $code = 9715;
	public const SYMBOLIC_CODE = 'is_not_bitrix24';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_HOST_NAME_IS_NOT_RECOGNIZED')
			?? 'Client with such name is not a Bitrix24 type.';
	}
}
