<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Im\V2\Entity\User\User;
use Bitrix\Im\V2\Entity\User\UserCollection;
use Bitrix\Im\V2\Rest\RestAdapter;
use Bitrix\ImOpenLines\Model\ConfigQueueTable;
use Bitrix\ImOpenLines\Model\UserLogTable;
use Bitrix\ImOpenLines\Model\UserOptionTable;
use Bitrix\ImOpenLines\User\Option;
use Bitrix\ImOpenLines\User\Log;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

class UserPause
{
	private int $userId;
	private Option $options;

	public function __construct(int $userId = 0)
	{
		if (!$userId)
		{
			$userId = \Bitrix\Im\User::getInstance()->getId();
		}

		$this->userId = $userId;
		$this->options = new Option($this->userId);
	}

	private function getLogger(): Log
	{
		return new Log($this->userId);
	}

	public function start(): bool
	{
		if ($this->options->getPause())
		{
			return false;
		}

		$this->options->setPause(true);
		Queue\Event::onAfterTMDayEnd(['USER_ID' => $this->userId]);
		$this->getLogger()->log(Log::TYPE_PAUSE, Log::TYPE_PAUSE_Y);

		return true;
	}

	public function stop(): bool
	{
		if (!$this->options->getPause())
		{
			return false;
		}

		$this->options->setPause(false);
		Queue\Event::onAfterTMDayStart(['USER_ID' => $this->userId]);
		$this->getLogger()->log(Log::TYPE_PAUSE, Log::TYPE_PAUSE_N);

		return true;
	}

	public function getStatus(): bool
	{
		return $this->options->getPause();
	}

	public static function getAllStatuses(int $configId = 0): array
	{
		if (!Loader::includeModule('im'))
		{
			return [];
		}

		$statuses = UserOptionTable::query()
			->setSelect(['USER_ID'])
			->addFilter('=PAUSE', 'Y')
		;

		if ($configId)
		{
			$subQuery = ConfigQueueTable::query()
				->setSelect(['ENTITY_ID'])
				->where('CONFIG_ID', $configId)
				->where('ENTITY_TYPE', 'user')
			;

			$statuses->whereIn('USER_ID', $subQuery);
		}

		$users = $statuses->fetchAll();
		$userIds = array_map(function ($user) {
			return (int)$user['USER_ID'];
		}, $users);

		$userCollection = new UserCollection($userIds);
		$users = (new RestAdapter($userCollection))->toRestFormat();

		$result = [];
		foreach ($users as $user)
		{
			$result[$user['id']] = $user;
		}

		return $result;
	}

	public static function getHistory(DateTime $dateStart, ?DateTime $dateEnd = null, int $configId = 0, int $userId = 0): array
	{
		if (!Loader::includeModule('im'))
		{
			return [];
		}

		$history = UserLogTable::query()
			->setSelect([
				'USER_ID',
				'DATA',
				'DATE_CREATE',
			])
			->setOrder(['DATE_CREATE' => 'ASC'])
			->addFilter('=TYPE', Log::TYPE_PAUSE)
			->addFilter('>=DATE_CREATE', $dateStart)
			->addFilter('<DATE_CREATE', $dateEnd ?? (new DateTime()))
		;

		if ($userId)
		{
			$history->addFilter('=USER_ID', $userId);
		}
		elseif ($configId)
		{
			$subQuery = ConfigQueueTable::query()
				->setSelect(['ENTITY_ID'])
				->where('CONFIG_ID', $configId)
				->where('ENTITY_TYPE', 'user')
			;

			$history->whereIn('USER_ID', $subQuery);
		}

		$result = $startDates = [];
		foreach ($history->fetchAll() as $row)
		{
			if (!isset($startDates[$row['USER_ID']]))
			{
				$startDates[$row['USER_ID']] = null;
			}

			if (!isset($result[$row['USER_ID']]))
			{
				$user = User::getInstance($row['USER_ID']);
				$result[$row['USER_ID']]['user'] = (new RestAdapter($user))->toRestFormat();
			}

			if ($row['DATA'] === 'Y')
			{
				$startDates[$row['USER_ID']] = $row['DATE_CREATE'];
			}
			else
			{
				$result[$row['USER_ID']]['history'][] = [
					'start' => $startDates[$row['USER_ID']] ? $startDates[$row['USER_ID']]->toString() : null,
					'end' => $row['DATE_CREATE']->toString(),
				];

				$startDates[$row['USER_ID']] = null;
			}
		}

		foreach ($startDates as $userId => $startDate)
		{
			if ($startDate)
			{
				$result[$userId]['history'][] = [
					'start' => $startDate->toString(),
					'end' => null
				];
			}
		}

		return $result;
	}
}
