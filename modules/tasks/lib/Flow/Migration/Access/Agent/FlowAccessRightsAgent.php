<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Access\Agent;

use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Flow\Control\Command\Access\GiveAccessCommand;
use Bitrix\Tasks\Flow\Internal\DI\Container;
use Bitrix\Tasks\Flow\Migration\Access\Exception\LockNotAcquiredException;
use Bitrix\Tasks\Internals\Log\Logger;
use Exception;

final class FlowAccessRightsAgent extends Stepper
{
	protected const LIMIT = 100;

	public function execute(array &$option): bool
	{
		$lastId = $option['lastId'] ?? 0;

		$service = Container::getInstance()->getFlowAccessRightsService();

		$command = new GiveAccessCommand($lastId, self::LIMIT);

		try
		{
			$newLastId = $service->setAccessRights($command);
		}
		catch (LockNotAcquiredException $exception)
		{
			Logger::handle($exception);

			return self::CONTINUE_EXECUTION;
		}
		catch (Exception $exception)
		{
			Logger::handle($exception);

			return self::FINISH_EXECUTION;
		}

		if ($newLastId === null)
		{
			return self::FINISH_EXECUTION;
		}

		$option['lastId'] = $newLastId;

		return self::CONTINUE_EXECUTION;
	}
}
