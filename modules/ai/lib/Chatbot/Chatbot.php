<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot;

use Bitrix\AI\Chatbot\Dto\MessageDto;
use Bitrix\AI\Chatbot\Enum\ChatInputStatus;
use Bitrix\AI\Chatbot\Message\GreetingMessage;
use Bitrix\AI\Chatbot\Message\Message;
use Bitrix\AI\Chatbot\Repository\ChatRepository;
use Bitrix\AI\Chatbot\Repository\ChatbotRepository;
use Bitrix\AI\Chatbot\Service\ChatbotService;
use Bitrix\AI\Chatbot\Service\ChatService;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;

abstract class Chatbot
{
	protected const MODULE_ID = 'ai';
	protected const BOT_CODE = '';

	final public function __construct()
	{}

	/**
	 * Register chatbot.
	 *
	 * @return int
	 * @throws SystemException
	 */
	final public function register(): int
	{
		$chatbotService = $this->getChatbotService();
		$chatbot = $chatbotService->getChatbotByCode(static::BOT_CODE);
		if ($chatbot)
		{
			return $chatbot->getId();
		}

		$chatbotService->createChatbot(static::MODULE_ID, static::BOT_CODE, static::class);
		$chatbot = $chatbotService->getChatbotByCode(static::BOT_CODE);
		if (!$chatbot)
		{
			throw new SystemException('Cannot create chatbot ' . static::BOT_CODE);
		}

		return $chatbot->getId();
	}

	/**
	 * Unregister chatbot.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	final public function unregister(): bool
	{
		$chatbotService = $this->getChatbotService();
		$chatbot = $chatbotService->getChatbotByCode(static::BOT_CODE);
		if (!$chatbot)
		{
			return true;
		}

		return $chatbotService->deleteChatbot($chatbot->getId())->isSuccess();
	}

	/**
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function onChatStart(int $chatId, array $parameters): void
	{
		$this->sendAnswer($chatId, new GreetingMessage('Welcome to ' . static::BOT_CODE . '!'));
	}

	/**
	 * @throws SystemException
	 * @throws \Exception
	 */
	final protected function startWriting(int $chatId, string $statusText = ''): void
	{
		$chatService = $this->getChatService();
		$chatService->setChatInputStatus($chatId, ChatInputStatus::Writing, $statusText);
	}

	/**
	 * @throws SystemException
	 * @throws \Exception
	 */
	final protected function stopWriting(int $chatId, bool $unlockInputStatus = true): void
	{
		$chatService = $this->getChatService();
		$inputStatus = $unlockInputStatus ? ChatInputStatus::Unlock : ChatInputStatus::Lock;
		$chatService->setChatInputStatus($chatId, $inputStatus);
	}

	public function onMessageAdd(MessageDto $message, ?array $command = null): void
	{
		//$this->sendAnswer($message->chatId, new DefaultMessage('answer'));
	}

	public function onAnswerAdd(MessageDto $message): void
	{
	}

	/**
	 * @throws SystemException
	 * @throws LoaderException
	 */
	final public function sendAnswer(int $chatId, Message $messageContent): void
	{
		$chatService = $this->getChatService();
		$messageId = $chatService->sendMessage($chatId, 0, $messageContent);
		$message = $chatService->getMessageById($chatId, $messageId);

		$this->onAnswerAdd($message);
	}

	/**
	 * get Messages by chatId.
	 *
	 * @param int $chatId
	 * @param int $limit
	 * @param int $offsetMessageId
	 *
	 * @return MessageDto[]
	 */
	final protected function getMessages(int $chatId, int $limit = 10, int $offsetMessageId = 0): array
	{
		return $this->getChatService()->getMessages($chatId, $limit, $offsetMessageId);
	}

	private function getChatService(): ChatService
	{
		static $chatService;
		if (!$chatService)
		{
			$chatService = new ChatService(new ChatRepository());
		}

		return $chatService;
	}

	private function getChatbotService(): ChatbotService
	{
		static $chatbotService;
		if (!$chatbotService)
		{
			$chatbotService = new ChatbotService(new ChatbotRepository());
		}

		return $chatbotService;
	}

	public function applyToChat(int $chatId): bool
	{
		return $this->getChatService()->changeChatbot($chatId, static::BOT_CODE);
	}
}
