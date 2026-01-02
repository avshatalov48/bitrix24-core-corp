<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal\Exception;

use \Bitrix\Main;

class ConsumeException extends UseCaseException
{
	public function getLocalizedMessage(): string
	{
		return Main\Localization\Loc::getMessage('BAAS_EXCEPTION_CONSUMPTION_MESSAGE')
			?? 'Consumption is failed';
	}
}
