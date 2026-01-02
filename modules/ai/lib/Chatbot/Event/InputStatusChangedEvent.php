<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Event;

use Bitrix\AI\Chatbot\Enum\ChatInputStatus;

class InputStatusChangedEvent extends PullEvent
{
	protected string $command = 'InputStatusChanged';
	public function __construct(
		array $users,
		protected ChatInputStatus $status,
		protected string $message,
	)
	{
		$this->users = $users;
	}

	protected function prepareData(): array
	{
		return [
			'status' => $this->status->value,
			'message' => $this->message
		];
	}
}