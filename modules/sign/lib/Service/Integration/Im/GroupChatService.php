<?php

namespace Bitrix\Sign\Service\Integration\Im;

use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Im\V2\Relation\AddUsersConfig;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Result\Service\Integration\Im\CreateGroupChatResult;

class GroupChatService
{
	private ?ChatFactory $chatFactory = null;

	public function __construct()
	{
		if ($this->isAvailable())
		{
			$this->chatFactory = ChatFactory::getInstance();
		}
	}

	public function isAvailable(): bool
	{
		return Loader::includeModule('im');
	}

	/**
	* @param array{USERS: int[], TITLE?: ?string, DESCRIPTION?: ?string} $chatParams
	*/
	public function createChat(array $chatParams): Result|CreateGroupChatResult
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_IM_MODULE_NOT_AVAILABLE')
			));
		}

		$chatFactoryResult = $this->chatFactory->addChat($chatParams);
		if (!$chatFactoryResult->isSuccess())
		{
			return (new Result())->addErrors($chatFactoryResult->getErrors());
		}

		$chatId = $chatFactoryResult->getResult()['CHAT_ID'] ?? null;
		if (is_numeric($chatId))
		{
			return new CreateGroupChatResult((int)$chatId);
		}

		return (new Result())->addError(new Error(
			Loc::getMessage('SIGN_INTEGRATION_ERROR_CHAT_ID_NOT_RECEIVED')
		));
	}

	public function addAdminByChatId(int $chatId, int $adminId): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_IM_MODULE_NOT_AVAILABLE')
			));
		}

		$chat = $this->chatFactory->getChat($chatId);
		if ($chat === null)
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_CHAT_NOT_FOUND')
			));
		}

		if (in_array($adminId, $chat->getUserIds() ?? [], true))
		{
			$chat->addManagers([$adminId]);
		}
		else
		{
			$chat->addUsers([$adminId], new AddUsersConfig([$adminId, false]));
		}

		return new Result();
	}

	public function addUsersByChatId(int $chatId, array $userIds): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_IM_MODULE_NOT_AVAILABLE')
			));
		}

		$chat = $this->chatFactory->getChat($chatId);
		if ($chat === null)
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_CHAT_NOT_FOUND')
			));
		}

		$chat->addUsers($userIds, new AddUsersConfig(hideHistory: false));

		return new Result();
	}

	public function setChatDescription(int $chatId, string $description): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_IM_MODULE_NOT_AVAILABLE')
			));
		}

		$chat = $this->chatFactory->getChat($chatId);
		if ($chat === null)
		{
			return (new Result())->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_CHAT_NOT_FOUND')
			));
		}

		$setDescriptionResult = $chat->setDescription($description)->save();
		if (!$setDescriptionResult->isSuccess())
		{
			return (new Result())->addErrors($setDescriptionResult->getErrors());
		}

		return new Result();
	}
}