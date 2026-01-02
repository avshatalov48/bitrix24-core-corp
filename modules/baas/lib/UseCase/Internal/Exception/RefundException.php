<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Exception;

use \Bitrix\Main;

class RefundException extends UseCaseException
{
	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_REFUND_MESSAGE')
			?? 'Refund has not been made';
	}
}
