<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External\Exception\BaasControllerException;

use \Bitrix\Main;

class BillingRespondsInWrongFormatException extends ExceptionFromError
{
	protected $code = 9701;

	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_BILLING_WRONG_FORMAT')
			?? 'Billing center answered in a wrong format.';
	}
}
