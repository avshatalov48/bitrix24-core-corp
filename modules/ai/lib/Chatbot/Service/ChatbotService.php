<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Service;

use Bitrix\AI\Chatbot\Model\Chatbot;
use Bitrix\AI\Chatbot\Repository\ChatbotRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\SystemException;

class ChatbotService
{
	private ChatbotRepository $chatbotRepository;

	public function __construct(ChatbotRepository $chatbotRepository)
	{
		$this->chatbotRepository = $chatbotRepository;
	}

	/**
	 * Get chatbot by code
	 */
	public function getChatbotByCode(string $chatbotCode): ?Chatbot
	{
		$chatbot = $this->chatbotRepository->findChatbotByCode($chatbotCode);
		if (!$chatbot)
		{
			return null;
		}

		return $chatbot;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getChatbotById(int $chatbotId): ?Chatbot
	{
		$chatbot = $this->chatbotRepository->findChatbotById($chatbotId);
		if (!$chatbot)
		{
			return null;
		}

		return $chatbot;
	}

	/**
	 * Create a new chatbot
	 */
	public function createChatbot(string $moduleId, string $code, string $class): ?int
	{
		if (!is_subclass_of($class, \Bitrix\AI\Chatbot\Chatbot::class))
		{
			return null;
		}
		$result = $this->chatbotRepository->addChatbot($moduleId, $code, $class);

		return $result->isSuccess() ? $result->getId() : null;
	}

	/**
	 * @param int $id
	 *
	 * @return DeleteResult
	 * @throws \Exception
	 */
	public function deleteChatbot(int $id): DeleteResult
	{
		return $this->chatbotRepository->deleteChatbot($id);
	}
}
