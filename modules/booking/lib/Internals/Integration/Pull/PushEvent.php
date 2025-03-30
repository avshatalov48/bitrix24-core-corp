<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Pull;

class PushEvent
{
	public function __construct(
		private readonly string $command,
		private readonly string $tag = '',
		private readonly array $recipients = [],
		private readonly array $params = [],
		private readonly int $entityId = 0,
	)
	{

	}

	public function getCommand(): string
	{
		return $this->command;
	}

	public function isTag(): bool
	{
		return $this->tag !== '';
	}

	public function getTag(): string
	{
		return $this->tag;
	}

	public function getRecipients(): array
	{
		return $this->recipients;
	}

	public function getParams(): array
	{
		return [
			'module_id' => 'booking',
			'command' => $this->getCommand(),
			'params' => $this->preparePullManagerParams($this->params),
			'skip_users' => !empty($this->params['currentUserId']) ? [$this->params['currentUserId']] : [],
		];
	}

	private function preparePullManagerParams(array $params): array
	{
		$pullManagerParams = [
			'eventName' => $this->getCommand(),
			'item' => [],
			'skipCurrentUser' => false,
			'eventId' => null,
			'entityId' => $this->entityId,
			'ignoreDelay' => false,
		];

		return array_merge($params, $pullManagerParams);
	}
}
