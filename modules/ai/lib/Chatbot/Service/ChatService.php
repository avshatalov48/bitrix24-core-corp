<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Service;

use Bitrix\AI;
use Bitrix\AI\Chatbot\Chatbot;
use Bitrix\AI\Chatbot\Dto\ChatInitDto;
use Bitrix\AI\Chatbot\Dto\MessageDto;
use Bitrix\AI\Chatbot\Model\Chat;
use Bitrix\AI\Chatbot\Model\Messages;
use Bitrix\AI\Chatbot\Enum\ChatInputStatus;
use Bitrix\AI\Chatbot\Enum\MessageType;
use Bitrix\AI\Chatbot\Event\InputStatusChangedEvent;
use Bitrix\AI\Chatbot\Event\NewMessageEvent;
use Bitrix\AI\Chatbot\Message\Message;
use Bitrix\AI\Chatbot\Repository\ChatRepository;
use Bitrix\AI\Chatbot\Repository\ChatbotRepository;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use DateTimeInterface;

class ChatService
{
	private ChatRepository $chatRepository;

	public function __construct(ChatRepository $chatRepository)
	{
		$this->chatRepository = $chatRepository;
	}

	/**
	 * Create or retrieve an existing chat based on chatbot code and entity details
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function initChat(ChatInitDto $chatInitDto): Chat
	{
		// Find the chatbot by its code
		$chatbot = $this->chatRepository->findChatbotByCode($chatInitDto->scenarioCode);
		if (!$chatbot)
		{
			throw new SystemException('Chatbot not found.');
		}

		// Check if a chat already exists
		if ($chatInitDto->chatId)
		{
			$chat = $this->getChatById($chatInitDto->chatId);
			if (!$chat)
			{
				throw new SystemException("Chat with ID:{$chatInitDto->chatId} not found.");
			}
		}
		else
		{
			$chat = $this->chatRepository->findChat($chatbot->getId(), $chatInitDto->entityType, $chatInitDto->entityId);
		}

		// If chat does not exist, create it
		if ($chat === null)
		{
			$code = $chatInitDto->entityType . '_' . $chatInitDto->entityId;
			$result = $this->chatRepository->createChat($code, $chatbot->getId(), $chatInitDto->userId);
			if (!$result->isSuccess())
			{
				throw new SystemException('Cannot create chat.');
			}
			$chat = $this->chatRepository->getChatById($result->getId());
			$this->createChatbot($chatbot)->onChatStart($chat->getId(), $chatInitDto->parameters);
			$this->setParam($chat->getId(), 'initialized', true);
		}

		return $chat;
	}

	/**
	 * @throws LoaderException
	 * @throws SystemException
	 */
	private function createChatbot(AI\Chatbot\Model\Chatbot $chatbot): Chatbot
	{
		if (
			!Loader::includeModule($chatbot->getModuleId())
			|| !class_exists($chatbot?->getClass())
			|| !is_subclass_of($chatbot?->getClass(), Chatbot::class)
		)
		{
			throw new ObjectNotFoundException("Chatbot class not found.");
		}

		return new ($chatbot?->getClass())();
	}

	public function changeChatbot(int $chatId, string $chatbotCode): bool
	{
		$chatbot = (new ChatbotService(new ChatbotRepository()))->getChatbotByCode($chatbotCode);
		if (!$chatbot)
		{
			return false;
		}

		$chat = $this->chatRepository->getChatById($chatId);
		if (!$chat)
		{
			return false;
		}

		return $chat
			->setChatbotId($chatbot->getId())
			->save()
			->isSuccess()
		;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getChatById(int $chatId): ?Chat
	{
		return $this->chatRepository->getChatById($chatId);
	}


	/**
	 * Send a message in a chat
	 * @throws ObjectNotFoundException|AccessDeniedException|SystemException|LoaderException
	 */
	public function sendMessage(int $chatId, int $authorId, Message $message): int
	{
		// Check chat's input status
		$chat = $this->chatRepository->getChatById($chatId);
		if (!$chat)
		{
			throw new ObjectNotFoundException("Chat with id:{$chatId} not found.");
		}

		if ($authorId !== 0 && ($authorId !== $chat->getAuthorId()))
		{
			throw new AccessDeniedException("This user can't write to this chat.");
		}

		if ($authorId !== 0 && $chat->getInputStatus() === 'Writing')
		{
			throw new AccessDeniedException("Chat is locked for input.");
		}

		$messageId = $this->chatRepository->addMessage($chatId, $authorId, $message);

		$newMessage = $this->getMessageById($chat->getId(), $messageId);

		if ($newMessage->authorId !== 0)
		{
			$chatbot = (new ChatbotService(new ChatbotRepository()))->getChatbotById($chat->getChatbotId());
			$command = ($newMessage->type === MessageType::ButtonClicked) ? $newMessage->params['command'] : null;
			if (!$chatbot)
			{
				throw new ObjectNotFoundException('Chatbot not found.');
			}
			$this->createChatbot($chatbot)->onMessageAdd($newMessage, $command);
		}

		$initialized = $chat->getParams()['initialized'] ?? false;
		if ($initialized)
		{
			(new NewMessageEvent([$chat->getAuthorId()], $newMessage))->send();
		}

		return $newMessage->id;
	}

	public function getCommandData(int $chatId, int $messageId, int $buttonId): ?array
	{
		$message = $this->getMessageById($chatId, $messageId);
		foreach ($message->params['buttons'] as $button)
		{
			if ($button['id'] === $buttonId)
			{
				return [$button['command'], $button['commandData']];
			}
		}

		return null;
	}

	/**
	 * Get messages by chatId.
	 *
	 * @param int $chatId
	 * @param int $limit
	 * @param int $offsetMessageId
	 *
	 * @return MessageDto[]
	 */
	public function getMessages(int $chatId, int $limit = 10, int $offsetMessageId = 0): array
	{
		$messages = $this->chatRepository->getMessages($chatId, $limit, $offsetMessageId);

		return array_reverse($this->getMessagesDto($messages));
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getMessageById(int $chatId, int $messageId): MessageDto
	{
		$message = $this->chatRepository->getMessageById($chatId, $messageId);

		return $this->convertMessageToDto($message);
	}



	/**
	 * @param Messages $messages
	 *
	 * @return MessageDto[]
	 */
	public function getMessagesDto(Messages $messages): array
	{
		$messagesDto = [];
		foreach ($messages as $message)
		{
			$messagesDto[] = $this->convertMessageToDto($message);
		}

		return $messagesDto;
	}

	private function convertMessageToDto(\Bitrix\AI\Chatbot\Model\Message $message): MessageDto
	{
		return new MessageDto(
			$message->getId(),
			$message->getChatId(),
			$message->getAuthorId(),
			MessageType::from($message->getType()),
			$message->getContent(),
			$message->getParams(),
			$message->getDateCreate()->format(DateTimeInterface::ATOM),
			false,
			!$message->getUnread()
		);
	}

	/**
	 * Mark message as viewed by user
	 * @throws ArgumentException
	 */
	public function markMessageAsViewed(int $chatId, array $messagesIds): void
	{
		$this->chatRepository->markMessageAsViewed($chatId, $messagesIds);
	}

	/**
	 * Lock or unlock a chat's input status
	 * @throws ObjectNotFoundException
	 * @throws LoaderException
	 * @throws \Exception
	 */
	public function setChatInputStatus(int $chatId, ChatInputStatus $status, string $message = ''): void
	{
		$chat = $this->chatRepository->getChatById($chatId);
		if (!$chat)
		{
			throw new ObjectNotFoundException('Chat was not found.');
		}

		$this->chatRepository->setChatInputStatus($chatId, $status);

		(new InputStatusChangedEvent([$chat->getAuthorId()], $status, $message))->sendImmediately();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function ButtonClicked(int $chatId, int $messageId, int $buttonId): void
	{
		$message = $this->chatRepository->getMessageById($chatId, $messageId);
		$params = $message->getParams();
		foreach ($params['buttons'] as $key => $button)
		{
			if ($button['id'] === $buttonId)
			{
				$params['buttons'][$key]['selected'] = true;
			}
		}
		$message->setParams($params);
		$message->save();
	}

	/**
	 * @param int $chatId
	 * @param mixed $param
	 *
	 * @return string|null
	 * @throws ObjectNotFoundException
	 * @throws SystemException
	 */
	public function getParam(int $chatId, string $param): mixed
	{
		$chat = $this->chatRepository->getChatById($chatId);
		if (!$chat)
		{
			throw new ObjectNotFoundException('Chat was not found.');
		}

		$params = $chat->getParams();

		return $params[$param] ?? null;
	}

	/**
	 * @param int $chatId
	 * @param string $param
	 * @param mixed $value
	 *
	 * @return void
	 * @throws ObjectNotFoundException
	 * * @throws SystemException
	 */
	public function setParam(int $chatId, string $param, mixed $value): void
	{
		$chat = $this->chatRepository->getChatById($chatId);
		if (!$chat)
		{
			throw new ObjectNotFoundException('Chat was not found.');
		}

		$params = $chat->getParams();
		$params[$param] = $value;
		$chat->setParams($params);
		$chat->save();
	}
}
