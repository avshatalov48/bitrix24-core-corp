<?php

namespace Bitrix\Tasks\Internals\Notification;

class StdOut implements ProviderInterface
{
	/** @var Message[]  */
	private array $messages = [];

	public function addMessage(Message $message): void
	{
		$this->messages[] = $message;
	}

	public function pushMessages(): void
	{
		file_put_contents('php://stdout', 'Stdout Provider');
		file_put_contents('php://stdout', "\n");

		foreach ($this->messages as $message)
		{
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', "====================================");
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', 'for: ' . $message->getRecepient()->getName());
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', 'from: ' . $message->getSender()->getName());
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', 'entity: ' . $message->getMetaData()->getEntityCode() . ' ' . $message->getMetaData()->getEntityOperation());
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', 'metadata: ' . json_encode($message->getMetaData()->getParams()));
			file_put_contents('php://stdout', "\n");
			file_put_contents('php://stdout', "====================================");
			file_put_contents('php://stdout', "\n");
		}
	}
}