<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientLicenseIsNotFoundException extends ClientException
{
	protected $code = 9716;
	public const SYMBOLIC_CODE = 'LICENSE_NOT_FOUND';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_LICENSE_IS_NOT_FOUND')
			?? 'The license has not been not found.';
	}
}
