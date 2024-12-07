<?php

namespace Bitrix\StaffTrack\Shift\Observer\Counter;

use Bitrix\Im\Common;
use Bitrix\Im\Dialog;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\StaffTrack\Dictionary\Mute;
use Bitrix\StaffTrack\Model\CounterTable;
use Bitrix\StaffTrack\Model\HandledChatTable;
use Bitrix\StaffTrack\Provider\CounterProvider;
use Bitrix\StaffTrack\Service\CounterService;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;

class Add implements ObserverInterface
{
	private CounterProvider $provider;
	private CounterService $service;
	private ShiftDto $shiftDto;
	private ?int $chatId = null;


	public function __construct()
	{
		$this->provider = CounterProvider::getInstance();
		$this->service = CounterService::getInstance();
	}

	/**
	 * @param ShiftDto $shiftDto
	 * @return void
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(ShiftDto $shiftDto): void
	{
		$this->shiftDto = $shiftDto;

		if ($this->shiftDto->skipCounter === true)
		{
			return;
		}

		$userCounter = $this->provider->get($this->shiftDto->userId);

		if (!$userCounter)
		{
			$this->service->save($this->shiftDto->userId, Mute::DISABLED);
		}

		if ($this->shiftDto->dialogId)
		{
			$this->handleDialogUserCounter();
		}
	}

	/**
	 * @return void
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	private function handleDialogUserCounter(): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		if (!Common::isChatId($this->shiftDto->dialogId))
		{
			return;
		}

		$this->chatId = Dialog::getChatId($this->shiftDto->dialogId, $this->shiftDto->userId);
		if (empty($this->chatId))
		{
			return;
		}

		if ($this->isChatHandled())
		{
			return;
		}

		$userIdList = $this->getChatUserList();
		if (empty($userIdList))
		{
			return;
		}

		$this->insertCounter($userIdList);
		$this->setChatHandled();
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function isChatHandled(): bool
	{
		$handledChat = HandledChatTable::query()
			->setSelect(['*'])
			->where('CHAT_ID', $this->chatId)
			->setCacheTtl(86400)
			->setLimit(1)
			->exec()->fetchObject()
		;

		return $handledChat !== null;
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function setChatHandled(): void
	{
		HandledChatTable::add([
			'CHAT_ID' => $this->chatId
		]);
	}

	/**
	 * @return array
	 */
	private function getChatUserList(): array
	{
		$relations = \Bitrix\Im\Chat::getRelation(
			$this->chatId,
			[
				'SELECT' => [
					'ID',
					'USER_ID'
				],
				'WITHOUT_COUNTERS' => 'Y'
			]
		);

		return array_keys($relations);
	}

	/**
	 * @param array $userIdList
	 * @return void
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function insertCounter(array $userIdList): void
	{
		$query = '';
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		foreach ($userIdList as $userId)
		{
			$query .= ($query !== '' ? ',' : '') . '(' . $userId . ')';
		}

		$sqlString = $helper->getInsertIgnore(CounterTable::getTableName(), "(USER_ID)", " VALUES " . $query);
		$connection->query($sqlString);

		CounterTable::cleanCache();
	}
}