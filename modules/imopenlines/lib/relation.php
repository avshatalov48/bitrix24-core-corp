<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Im\Model\MessageUnreadTable;
use Bitrix\Im\V2\Chat\ChatFactory;
use Bitrix\Im\V2\Message\CounterService;
use Bitrix\ImOpenLines\Model\RecentTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Loader;
use Bitrix\Pull\Event;

class Relation
{
	private int $chatId;
	private array $userIds = [];
	private ?array $line = null;
	private int $userId;

	public function __construct(int $chatId)
	{
		$this->chatId = $chatId;
		$this->preloadFakeRelations($this->chatId);
	}

	public function addRelation(int $userId): void
	{
		$this->userId = $userId;
		$this->userIds = array_unique(array_merge($this->userIds, [$this->userId]));

		$chat = ChatFactory::getInstance()->getChatById($this->chatId);
		$session = SessionTable::getRow([
			'filter' => [
				'=CHAT_ID' => $this->chatId
			]
		]);
		Recent::setRecent($this->userId, $this->chatId, $chat->getLastMessageId(), $session['ID']);

		$this->sendPullAdd();
	}

	public function removeRelation(int $userId): void
	{
		$this->userId = $userId;
		$this->userIds = array_unique(array_diff($this->userIds, [$this->userId]));

		$this->sendPullChatHide();

		Recent::removeRecent($this->userId, $this->chatId);
	}

	public function removeAllRelations(bool $force = false, array $excludeUserIds = []): void
	{
		$this->userIds = array_diff($this->userIds, $excludeUserIds);
		foreach ($this->userIds as $userId)
		{
			if ($force || !isset($this->userId) || $this->userId !== $userId)
			{
				$this->removeRelation($userId);
			}
		}
	}

	private function sendPullAdd(): bool
	{
		if (!Loader::includeModule('pull'))
		{
			return false;
		}

		$users = \CIMContactList::GetUserData([
			'ID' => [$this->userId],
			'DEPARTMENT' => 'N',
			'USE_CACHE' => 'N'
		]);

		$pushMessage = [
			'module_id' => 'im',
			'command' => 'chatUserAdd',
			'params' => [
				'chatId' => $this->chatId,
				'dialogId' => 'chat' . $this->chatId,
				'chatTitle' => '',
				'chatOwner' => $this->userId,
				'chatExtranet' => 'N',
				'users' => $users['users'],
				'newUsers' => [$this->userId],
				'userCount' => count($this->userIds),
				'date' => date('H:i:s'),
				'lines' => $this->getLine()
			],
			'extra' => \Bitrix\Im\Common::getPullExtra()
		];

		Event::add([$this->userId], $pushMessage);
		return \CPullWatch::AddToStack('IM_PUBLIC_' . $this->chatId, $pushMessage);
	}

	private function sendPullChatHide(): bool
	{
		$pushMessage = [
			'module_id' => 'im',
			'command' => 'chatHide',
			'expiry' => 3600,
			'params' => [
				'dialogId' => 'chat' . $this->chatId,
				'chatId' => $this->chatId,
				'lines' => true,
			],
			'extra' => \Bitrix\Im\Common::getPullExtra()
		];

		$userIds = array_unique(array_merge($this->userIds, [$this->userId]));
		Event::add($userIds, $pushMessage);
		return \CPullWatch::AddToStack('IM_PUBLIC_' . $this->chatId, $pushMessage);
	}

	private function getLine(): ?array
	{
		if (is_array($this->line))
		{
			return $this->line;
		}

		$session = SessionTable::getRow([
			'select' => ['ID', 'STATUS', 'DATE_CREATE'],
			'filter' => [
				'=CHAT_ID' => $this->chatId,
			]
		]);
		if (is_null($session))
		{
			return null;
		}

		$this->line = [
			'id' => (int)$session['ID'],
			'status' => (int)$session['STATUS'],
			'date_create' => $session['DATE_CREATE'],
		];

		return $this->line;
	}

	private function preloadFakeRelations(int $chatId): void
	{
		$relations = RecentTable::getList([
			'select' => ['USER_ID'],
			'filter' => [
				'=CHAT_ID' => $chatId
			]
		]);

		foreach ($relations->fetchAll() as $relation)
		{
			$this->userIds[] = (int)$relation['USER_ID'];
		}
	}

	public function getRelationUserIds(): array
	{
		return $this->userIds;
	}
}
