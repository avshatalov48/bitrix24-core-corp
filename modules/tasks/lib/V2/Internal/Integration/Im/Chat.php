<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Im\V2\Chat\ExternalChat\Event\RegisterTypeEvent;
use Bitrix\Im\V2\Chat\ExternalChat\Config;
use Bitrix\Im\V2\Permission\Action;
use Bitrix\Im\V2\Chat\ExternalChat\Event\FilterUsersByAccessEvent;
use Bitrix\Im\V2\Relation\AddUsersConfig;
use Bitrix\Im\V2\Chat\ChatFactory;

class Chat
{
	public const ENTITY_TYPE = 'TASKS_TASK';

	public static function onRegisterType(RegisterTypeEvent $event): EventResult
	{
		$parameters = [
			'type' => self::ENTITY_TYPE,
			'config' => new Config(
				hasOwnRecentSection: false,
				permissions: [
					Action::Call->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
					Action::Extend->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
					Action::ChangeAvatar->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
					Action::ChangeDescription->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
					Action::ChangeColor->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
					Action::Rename->value => \Bitrix\Im\V2\Chat::ROLE_NONE,
			],
				isAutoJoinEnabled: true),
		];

		return new EventResult(EventResult::SUCCESS, $parameters);
	}

	public static function onFilterUsersByAccess(FilterUsersByAccessEvent $event): EventResult
	{
		$userIds = $event->getUserIds();
		$taskId = (int)$event->getChat()->getEntityId();
		$usersWithAccess = Container::getInstance()
			->getTaskAccessService()
			->filterUsersWithAccess($taskId, $userIds)
		;

		return new EventResult(EventResult::SUCCESS, ['userIds' => $usersWithAccess]);
	}

	public function addChat(Task $task): ?int
	{
		if (!Loader::includeModule('im'))
		{
			return null;
		}

		$factory = ChatFactory::getInstance();
		$result = $factory->addUniqueChat([
			'TYPE' => \Bitrix\Im\V2\Chat::IM_TYPE_EXTERNAL,
			'ENTITY_TYPE' => self::ENTITY_TYPE,
			'ENTITY_ID' => $task->getId(),
			'USERS' => $task->getMemberIds(),
		]);

		if (!$result->isSuccess())
		{
			return null;
		}

		return (int)$result->getResult()['CHAT_ID'];
	}

	/**
	 * @param int[] $membersToAdd
	 * @throws LoaderException
	 */
	public function addChatMembers(Task $task, array $membersToAdd = []): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if (!$task->chatId)
		{
			return;
		}

		if (empty($membersToAdd))
		{
			return;
		}

		\Bitrix\Im\V2\Chat::getInstance($task->chatId)?->addUsers($membersToAdd, new AddUsersConfig(withMessage: false));
	}

	/**
	 * @param int[] $membersToHide
	 * @throws LoaderException
	 */
	public function hideChatMembers(Task $task, array $membersToHide = []): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if (!$task->chatId)
		{
			return;
		}

		if (empty($membersToHide))
		{
			return;
		}

		foreach ($membersToHide as $userId)
		{
			\Bitrix\Im\V2\Chat::getInstance($task->chatId)?->hideUser($userId);
		}
	}
}
