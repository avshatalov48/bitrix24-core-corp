<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Trait;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Rest\Controllers\Trait\ErrorResponseTrait;

trait AccessErrorTrait
{
	use ErrorResponseTrait;

	protected function buildForbiddenResponse(?Error $error = null): mixed
	{
		$error ??= new Error(Loc::getMessage('TASKS_ACCESS_ERROR_DEFAULT'));

		$this->errorCollection->setError($error);

		return null;
	}
}