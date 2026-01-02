<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Repository;

use Bitrix\AI\Chatbot\Model\Chatbot;
use Bitrix\AI\Chatbot\Model\ChatTable;
use Bitrix\AI\Chatbot\Model\MessageTable;
use Bitrix\AI\Chatbot\Model\MessageUnreadTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\SystemException;
use Bitrix\AI\Chatbot\Model\ChatbotTable;

class ChatbotRepository
{
	public function __construct()
	{
	}

	/**
	 * @param string $chatbotCode
	 *
	 * @return ?Chatbot
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findChatbotByCode(string $chatbotCode): ?Chatbot
	{
		return ChatbotTable::query()->setSelect(['*'])->where('CODE', $chatbotCode)->fetchObject();
	}

	/**
	 * @param int $chatbotId
	 *
	 * @return ?Chatbot
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function findChatbotById(int $chatbotId): ?Chatbot
	{
		return ChatbotTable::query()->setSelect(['*'])->where('ID', $chatbotId)->fetchObject();
	}

	/**
	 * @param string $moduleId
	 * @param string $code
	 * @param string $class
	 *
	 * @return AddResult
	 */
	public function addChatbot(string $moduleId, string $code, string $class): AddResult
	{
		$newChatbot = new Chatbot();
		$newChatbot->setModuleId($moduleId)
			->setCode($code)
			->setClass($class)
		;

		return $newChatbot->save();
	}

	/**
	 * @param $id
	 *
	 * @return DeleteResult
	 * @throws \Exception
	 */
	public function deleteChatbot($id): DeleteResult
	{
		// delete all chatbot data
		$chatIds = ChatTable::query()->setSelect(['ID'])->where('CHATBOT_ID', $id)->fetchCollection()->getIdList();
		ChatTable::deleteByFilter(['=CHATBOT_ID' => $id]);
		MessageTable::deleteByFilter(['=CHAT_ID' => $chatIds]);
		MessageUnreadTable::deleteByFilter(['=CHAT_ID' => $chatIds]);

		return ChatbotTable::delete($id);
	}
}
