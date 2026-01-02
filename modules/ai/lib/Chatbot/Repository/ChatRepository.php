<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Repository;

use Bitrix\AI\Chatbot\Model;
use Bitrix\AI\Chatbot\Model\Chat;
use Bitrix\AI\Chatbot\Model\Chatbot;
use Bitrix\AI\Chatbot\Model\Messages;
use Bitrix\AI\Chatbot\Model\MessageUnread;
use Bitrix\AI\Chatbot\Enum\ChatInputStatus;
use Bitrix\AI\Chatbot\Message;
use Bitrix\AI\Chatbot\Model\ChatTable;
use Bitrix\AI\Chatbot\Model\MessageTable;
use Bitrix\AI\Chatbot\Model\MessageUnreadTable;
use Bitrix\AI\Chatbot\Service\ChatbotService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\SystemException;

class ChatRepository
{
	public function __construct()
	{
	}

	/**
	 * Find chatbot by code
	 */
	public function findChatbotByCode(string $chatbotCode): ?Chatbot
	{
		return (new ChatbotService(new ChatbotRepository()))->getChatbotByCode($chatbotCode);
	}

	/**
	 * Find chat by chatbot ID, entity type, and entity ID
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findChat(int $chatbotId, string $entityType, string $entityId): ?Chat
	{
		$code = $entityType . '_' . $entityId;

		return ChatTable::query()
			->where('CHATBOT_ID', $chatbotId)
			->where('CODE', $code)
			->fetchObject()
		;
	}

	/**
	 * Create a new chat
	 *
	 */
	public function createChat(string $code, int $chatbotId, int $userId): Result
	{
		$newChat = new Chat();
		$newChat->setCode($code)
			->setChatbotId($chatbotId)
			->setAuthorId($userId)
		;

		return $newChat->save();
	}

	/**
	 * Get chat details by ID
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getChatById(int $chatId): ?Chat
	{
		return ChatTable::query()
			->setSelect(['*'])
			->where('ID', $chatId)
			->fetchObject()
		;
	}

	/**
	 * Send message in chat
	 *
	 */
	public function addMessage($chatId, $authorId, Message\Message $message): ?int
	{
		$newMessage = new Model\Message();
		$newMessage->setChatId($chatId)
			->setAuthorId($authorId)
			->setType($message->getType()->value)
			->setContent($message->getContent())
			->setParams($message->getParams())
		;
		$result = $newMessage->save();
		$messageId = $result->isSuccess() ? $result->getId() : null;
		if ($messageId && $authorId === 0)
		{
			(new MessageUnread())->setChatId($chatId)
				->setAuthorId($authorId)
				->setMessageId($newMessage->getId())
			;
		}

		return $messageId;
	}

	/**
	 * Get chat messages
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMessages(int $chatId, int $limit = 10, int $offsetMessageId = 0): Messages
	{
		$messages = MessageTable::query()
			->setSelect(['*','UNREAD'])
			->where('CHAT_ID', $chatId)
			->setOrder(['ID' => 'DESC'])
			->setLimit($limit)
		;
		if ($offsetMessageId > 0)
		{
			$messages->where('ID', '<', $offsetMessageId);
		}

		return $messages->fetchCollection();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getMessageById(int $chatId, int $messageId): Model\Message
	{
		return MessageTable::query()
			->setSelect(['*', 'UNREAD'])
			->where('ID', $messageId)
			->where('CHAT_ID', $chatId)
			->fetchObject()
		;
	}

	/**
	 * Mark message as viewed
	 *
	 * @throws ArgumentException
	 */
	public function markMessageAsViewed(int $chatId, array $messagesIds): void
	{
		MessageUnreadTable::deleteByFilter([
			'=CHAT_ID' => $chatId,
			'=MESSAGE_ID' => $messagesIds,
		]);
	}

	/**
	 * Set chat input status (lock/unlock)
	 *
	 * @throws \Exception
	 */
	public function setChatInputStatus(int $chatId, ChatInputStatus $status): void
	{
		ChatTable::update($chatId, ['INPUT_STATUS' => $status->value]);
	}
}
