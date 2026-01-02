<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase;

use Bitrix\Main;

abstract class BaasException extends Main\SystemException
{
	protected $message;
	protected $code;

	public function __construct(?string $message = null)
	{
		if (!empty($message))
		{
			$this->message = $message;
		}
		else
		{
			$this->message = $this->getLocalizedMessage();
		}

		parent::__construct($this->message, $this->code);
	}

	abstract public function getLocalizedMessage(): string;
}
