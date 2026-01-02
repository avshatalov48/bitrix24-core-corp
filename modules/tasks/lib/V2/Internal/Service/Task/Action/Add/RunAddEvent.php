<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Util;
use Exception;

class RunAddEvent
{
	use ConfigTrait;

	public function __invoke(array $fields)
	{
		$parameters = [
			'USER_ID' => $this->config->getUserId(),
		];

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskAdd', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, [$fields['ID'], &$fields, $parameters]);
			}
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
			Util::log($exception);
		}

		return $fields;
	}
}