<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;


class ClientException extends ExceptionFromError
{
	protected $code = 9720;

	public function getLocalizedMessage(): string
	{
		return 'There is some error with the client answer to the BaasController.';
	}
}
