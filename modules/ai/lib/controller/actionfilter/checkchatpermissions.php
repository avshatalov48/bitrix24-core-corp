<?php declare(strict_types=1);

namespace Bitrix\AI\Controller\ActionFilter;

use Bitrix\AI\Chatbot\Service\ChatService;
use Bitrix\AI\Facade\User;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Event;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

class CheckChatPermissions extends Base
{
	/**
	 * @throws SystemException
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		/** @var Action $action */
		$action = $event->getParameter('action');

		$arguments = $action->getArguments();
		$chatService = $arguments['chatService'] ?? null;
		$chatId = $arguments['chatId'] ?? null;

		if (empty($chatService) || !($chatService instanceof ChatService) || empty($chatId))
		{
			$this->addError(new Error('Missing chatService or chatId.'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$result = $this->checkPermissions($chatService, (int)$chatId);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	private function checkPermissions(ChatService $chatService, int $chatId): Result
	{
		$result = new Result();

		if (empty($chatId))
		{
			$result->addError(new Error('ChatId is required.'));

			return $result;
		}

		$chat = $chatService->getChatById($chatId);
		if ($chat === null)
		{
			$result->addError(new Error("ChatId: {$chatId} not found."));

			return $result;
		}

		$userId = User::getCurrentUserId();
		if ($chat->getAuthorId() !== $userId)
		{
			$result->addError(new Error("User '{$userId}' does not have permissions to chat {$chat->getId()}."));

			return $result;
		}

		return $result;
	}
}
