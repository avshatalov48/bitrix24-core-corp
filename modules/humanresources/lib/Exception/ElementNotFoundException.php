<?php

namespace Bitrix\HumanResources\Exception;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\ErrorMessage;

class ElementNotFoundException extends ResultContainedException
{
	public function __construct(string $message = '', int $code = 0, \Throwable|null $previous = null)
	{
		parent::__construct($message, $code, $previous);

		if ($message)
		{
			$this->setErrors(new ErrorCollection([
				new Error($message)
			]));
		}
	}
}