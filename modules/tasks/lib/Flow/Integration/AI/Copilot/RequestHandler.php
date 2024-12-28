<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Copilot;

use Bitrix\AI\Engine\IEngine;
use Bitrix\AI\Result;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Integration\AI\Control\AdviceService;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\ReplaceAdviceCommand;

class RequestHandler
{
	public static function onCompletions(Result $result, int $flowId): void
	{
		$advice = $result->getPrettifiedData();
		if (empty($advice))
		{
			return;
		}

		try
		{
			$adviceDecoded = Json::decode($advice);
		}
		catch (ArgumentException)
		{
			return;
		}

		$command =
			(new ReplaceAdviceCommand())
				->setFlowId($flowId)
				->setAdvice($adviceDecoded)
		;

		/** @var AdviceService $adviceService */
		$adviceService = ServiceLocator::getInstance()->get('tasks.flow.copilot.advice.service');
		$adviceService->replace($command);
	}

	public static function onQueueJobExecute(Event $event): EventResult
	{
		$result = $event->getParameter('result');
		if (!$result instanceof Result)
		{
			return new EventResult(EventResult::ERROR);
		}

		$engine = $event->getParameter('engine');
		if (!$engine instanceof IEngine)
		{
			return new EventResult(EventResult::ERROR);
		}

		$flowId = (int)($engine->getParameters()['flowId'] ?? 0);
		if ($flowId <= 0)
		{
			return new EventResult(EventResult::ERROR);
		}

		$advice = $result->getPrettifiedData();
		if (empty($advice))
		{
			return new EventResult(EventResult::ERROR);
		}

		try
		{
			$adviceDecoded = Json::decode($advice);
		}
		catch (ArgumentException)
		{
			return new EventResult(EventResult::ERROR);
		}

		$command =
			(new ReplaceAdviceCommand())
				->setFlowId($flowId)
				->setAdvice($adviceDecoded)
		;

		$adviceService = ServiceLocator::getInstance()->get('tasks.flow.copilot.advice.service');
		$adviceService->replace($command);

		return new EventResult(EventResult::SUCCESS);
	}
}
