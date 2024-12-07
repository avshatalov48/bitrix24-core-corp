<?php

namespace Bitrix\Transformer\Integration\Analytics;

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\Integration\Baas;

/**
 * @internal
 */
final class Registrar
{
	public function __construct(
		private readonly Baas\Feature $dedicatedControllerFeature,
		private readonly ?Uri $dedicatedControllerUri,
	)
	{
	}

	public function registerCommandSend(Command $command): void
	{
		if (!in_array($command->getStatus(), [Command::STATUS_SEND, Command::STATUS_ERROR], true))
		{
			throw new InvalidOperationException('Cant register send on command that is not in send or error status');
		}

		$this->buildEvent($command, 'send')?->send();
	}

	public function registerCommandFinish(Command $command): void
	{
		$event = $this->buildEvent($command, 'finish');
		if (!$event)
		{
			return;
		}

		$duration = $command->getTime()->getTimestamp() - $command->getSendTime()->getTimestamp();
		$event->setP4('duration_' . $duration);

		$event->send();
	}

	private function buildEvent(Command $command, string $eventName): ?AnalyticsEvent
	{
		$event = new AnalyticsEvent($eventName, 'transformer', 'commands');

		if ($command->getQueue())
		{
			$event->setElement($command->getQueue());
		}

		$type = $this->getTypeByCommandName($command->getCommandName());
		if (!$type)
		{
			return null;
		}

		$event->setType($type);

		$controllerUri = new Uri($command->getControllerUrl());
		$event->setP1('controllerHost_' . $controllerUri->getHost());

		if ($command->getFileSize() !== null)
		{
			$event->setP2('fileSize_' . $command->getFileSize());
		}

		if ($this->dedicatedControllerFeature->isApplicableToCommand($command))
		{
			$isSentToDedicatedController = $this->dedicatedControllerUri?->getHost() === $controllerUri->getHost();

			$event->setP3('baasDedicatedController_' . (int)$isSentToDedicatedController);
		}

		$event->setP5("guid_{$command->getGuid()}");

		$error = $command->getError();
		if ($error)
		{
			$event->setStatus('error_' . ($error->getCustomData()['jsonCode'] ?? 'noJsonCode'));
		}
		else
		{
			$event->setStatus('success');
		}

		return $event;
	}

	private function getTypeByCommandName(string $commandName): ?string
	{
		static $knownTypes = [
			'document',
			'video',
		];

		$fqnParts = explode('\\', $commandName);
		$className = end($fqnParts);

		$classNameLower = mb_strtolower($className);
		if (in_array($classNameLower, $knownTypes, true))
		{
			return $classNameLower;
		}

		return null;
	}
}
