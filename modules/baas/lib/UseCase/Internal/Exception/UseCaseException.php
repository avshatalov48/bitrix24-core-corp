<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Exception;

use \Bitrix\Baas;

abstract class UseCaseException extends Baas\UseCase\BaasException
{
	public function __construct(?string $message = null)
	{
		$res = $this->getLocalizedMessage();

		if (!empty($message))
		{
			$res .= ': ' . $message;
		}
		else
		{
			$res .= '.';
		}

		parent::__construct($res);
	}
}
