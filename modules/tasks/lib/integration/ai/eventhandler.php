<?php

namespace Bitrix\Tasks\Integration\AI;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\AI\Event\Factory\EventControllerFactory;
use Bitrix\Tasks\Integration\AI\Event\Message\MessageCollection;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Throwable;

final class EventHandler
{
	private const SEPARATOR = '_';
	private const MODULE_ID = 'tasks';

	public static function onContextGetMessages(Event $event): array
	{
		$handler = new self($event);
		$response = $handler->makeResponse();
		if (!$handler->isForMe())
		{
			return $response;
		}

		try
		{
			$controller = EventControllerFactory::getController(
				$handler->getContext(),
				$handler->getTaskId(),
				$handler->getXmlId()
			);

			$messages = $controller->getAdditionalMessages();
			$messages->add($controller->getMainMessage());
		}
		catch (Throwable $throwable)
		{
			LogFacade::logThrowable($throwable);
			return $response;
		}

		return $handler->makeResponse($messages);
	}

	private function isForMe(): bool
	{
		return Loader::includeModule(self::MODULE_ID)
			&& $this->event->getParameter('module') === self::MODULE_ID;
	}

	private function getContext(): string
	{
		return explode(self::SEPARATOR, $this->event->getParameter('id'))[0];
	}

	private function getXmlId(): string
	{
		return $this->event->getParameter('params')['xmlId'];
	}

	private function getTaskId(): int
	{
		return explode(self::SEPARATOR, $this->getXmlId())[1];
	}

	private function makeResponse(?MessageCollection $messages = null): array
	{
		return ['messages' => is_null($messages) ? [] : $messages->toArray()];
	}

	public function __construct(private Event $event)
	{

	}
}
