<?php

namespace Bitrix\Tasks\Internals\Notification;

class Log implements ProviderInterface
{
	/** @var Message[]  */
	private array $messages = [];

	public function addMessage(Message $message): void
	{
		$this->messages[] = $message;
	}

	public function pushMessages(): void
	{
		$logMessage = "Tasks notifications(Kibana provider)\n";

		foreach ($this->messages as $message)
		{
			$logMessage .= "\n";
			$logMessage .= '====================================';
			$logMessage .= "\n";
			$logMessage .= 'for: ' . $message->getRecepient()->getName() . '(' . $message->getRecepient()->getId() . ')';
			$logMessage .= "\n";
			$logMessage .= 'from: ' . $message->getSender()->getName() . '(' . $message->getSender()->getId() . ')';
			$logMessage .= "\n";
			$logMessage .= 'entity: ' . $message->getMetaData()->getEntityCode() . ' ' . $message->getMetaData()->getEntityOperation();
			$logMessage .= "\n";
			$logMessage .= 'metadata: ' . json_encode($message->getMetaData()->getParams());
			$logMessage .= "\n";
			$logMessage .= '====================================';
			$logMessage .= "\n";
		}

		AddMessage2Log($logMessage, 'tasks', 0);
	}
}