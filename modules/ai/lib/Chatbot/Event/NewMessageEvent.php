<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Event;

use Bitrix\AI\Chatbot\Dto\MessageDto;

class NewMessageEvent extends PullEvent
{
	protected string $command = 'newMessage';

	public function __construct(
		array $users,
		protected MessageDto $message
	)
	{
		$this->users = $users;
	}

	protected function prepareData(): array
	{
		return [
			'message' => $this->message,
		];
	}
}