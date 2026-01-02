<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class ClientRespondsInWrongFormatException extends ClientException
{
	protected $code = 9714;
	public const SYMBOLIC_CODE = 'wrong_format';

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CLIENT_RESPONDS_IN_WRONG_FORMAT')
			?? 'The client responds in a wrong format to the BaasController.';
	}

	/**
	 * @param Main\Error|null $error
	 */
	public function setError(?Main\Error $error): static
	{
		parent::setError($error);

		$this->message .= ' [' . $error->getMessage() . ' ]';

		return $this;
	}
}
