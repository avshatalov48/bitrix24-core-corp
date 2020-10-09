<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Im\User;

use \Bitrix\ImOpenLines\Model\QueueTable;

Loc::loadMessages(__FILE__);

class QueueManager
{
	private $error = null;
	private $id = null;
	private $config = null;

	const EVENT_QUEUE_OPERATORS_ADD = 'OnQueueOperatorsAdd';
	const EVENT_QUEUE_OPERATORS_DELETE = 'OnQueueOperatorsDelete';
	const EVENT_QUEUE_OPERATORS_CHANGE = 'OnQueueOperatorsChange';

	public function __construct($id, $config = array())
	{
		$this->error = new BasicError(null, '', '');
		$this->id = intval($id);
		$this->config = $config;
		Loader::includeModule("im");
	}

	public function updateUsers($users, $usersFields = array())
	{
		$addQueue = Array();
		$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();

		$businessUsers = Limit::getLicenseUsersLimit();
		if ($businessUsers !== false)
		{
			$users = array_intersect($users, $businessUsers);
		}
		foreach ($users as $userId)
		{
			if (!User::getInstance($userId)->isExtranet())
			{
				$addQueue[$userId] = $userId;
			}
		}

		$inQueue = Array();
		$orm = QueueTable::getList(array(
									   'filter' => Array('=CONFIG_ID' => $this->id)
								   ));
		while ($row = $orm->fetch())
		{
			$inQueue[$row['ID']] = $row['USER_ID'];
		}

		$queueList['QUEUE_BEFORE'] = array_values($inQueue);

		if (implode('|', $addQueue) != implode('|', $inQueue))
		{
			foreach ($inQueue as $id => $userId)
			{
				QueueTable::delete($id);
				$taggedCache->clearByTag(Queue::getUserCacheTag($userId, $this->id));
				unset($inQueue[$id]);
			}
			foreach ($addQueue as $userId)
			{
				$data = array(
					"CONFIG_ID" => $this->id,
					"USER_ID" => $userId,
				);

				if (!empty($usersFields[$userId]))
				{
					$data['USER_NAME'] = $usersFields[$userId]['USER_NAME'];
					$data['USER_WORK_POSITION'] = $usersFields[$userId]['USER_WORK_POSITION'];
					$data['USER_AVATAR'] = $usersFields[$userId]['USER_AVATAR'];
					$data['USER_AVATAR_ID'] = $usersFields[$userId]['USER_AVATAR_ID'];
				}
				$orm = QueueTable::add($data);
				$inQueue[$orm->getId()] = $userId;
			}
		}
		elseif(!empty($usersFields) && is_array($usersFields))
		{
			foreach ($inQueue as $id => $userId)
			{
				if (!empty($usersFields[$userId]))
				{
					$data['USER_NAME'] = $usersFields[$userId]['USER_NAME'];
					$data['USER_WORK_POSITION'] = $usersFields[$userId]['USER_WORK_POSITION'];
					$data['USER_AVATAR'] = $usersFields[$userId]['USER_AVATAR'];
					$data['USER_AVATAR_ID'] = $usersFields[$userId]['USER_AVATAR_ID'];

					QueueTable::update($id, $data);
					$taggedCache->clearByTag(Queue::getUserCacheTag($userId, $this->id));
				}
			}
		}

		if (empty($inQueue))
		{
			if ($businessUsers === false || !isset($businessUsers[0]))
			{
				$userId = User::getInstance()->getId();
			}
			else
			{
				$userId = $businessUsers[0];
			}

			if ($userId)
			{
				$inQueue[] = $userId;
				$data = array(
					"CONFIG_ID" => $this->id,
					"USER_ID" => $userId,
				);
				$userFields = $this->getUserFields($userId);

				if (!empty($userFields))
				{
					$data = array_merge($data, $userFields);
					$data['USER_AVATAR'] = '';
				}

				QueueTable::add($data);
			}
		}

		$queueList['QUEUE_AFTER'] = array_values($inQueue);
		$this->sendQueueChangeEvents($queueList['QUEUE_BEFORE'], $queueList['QUEUE_AFTER']);

		return true;
	}

	/**
	 * Return system user fields values for queue fields
	 *
	 * @param $userId
	 *
	 * @return array
	 */
	private function getUserFields($userId)
	{
		$fields = array();
		$user = User::getInstance($userId);

		if ($user->getId() == intval($userId))
		{
			$fields['USER_NAME'] = $user->getFullName();
			$fields['USER_WORK_POSITION'] = $user->getWorkPosition();
			$avatar = $user->getAvatar();

			if (mb_substr($avatar, 0, 1) == '/')
			{
				$avatar = \Bitrix\ImOpenLines\Common::getServerAddress() . $avatar;
			}

			$fields['USER_AVATAR'] = $avatar;
		}

		return $fields;
	}

	public static function checkBusinessUsers()
	{
		$businessUsers = Limit::getLicenseUsersLimit();
		if ($businessUsers === false)
		{
			return false;
		}
		$orm = QueueTable::getList(Array(
			'select' => Array('ID'),
			'filter' => Array(
				'!=USER_ID' => $businessUsers
			)
		));
		while($row = $orm->fetch())
		{
			QueueTable::delete($row['ID']);
		}

		return true;
	}

	public function getError()
	{
		return $this->error;
	}

	/**
	 * Get diff between old queue and new queue and send queue operators change events
	 *
	 * @param $queueBefore
	 * @param $queueAfter
	 */
	private function sendQueueChangeEvents($queueBefore, $queueAfter): void
	{
		$queueRemoved = array_diff($queueBefore, $queueAfter); //list of removed operators
		$queueAdded = array_diff($queueAfter, $queueBefore); //list of added operators

		if (!empty($queueRemoved))
		{
			$this->sendQueueOperatorsDeleteEvent($queueRemoved);
		}

		if (!empty($queueAdded))
		{
			$this->sendQueueOperatorsAddEvent($queueAdded);
		}

		if (!empty($queueAdded) || !empty($queueRemoved))
		{
			$this->sendQueueOperatorsChangeEvent($queueBefore, $queueAfter);
		}
	}

	/**
	 * Send event with list of added to line queue operators
	 *
	 * @param $operators
	 */
	private function sendQueueOperatorsAddEvent($operators)
	{
		$eventData = array(
			'line' => $this->id,
			'operators' => $operators
		);
		$event = new \Bitrix\Main\Event('imopenlines', self::EVENT_QUEUE_OPERATORS_ADD, $eventData);
		$event->send();
	}

	/**
	 * Send event with list of removed from line queue operators
	 *
	 * @param $operators
	 */
	private function sendQueueOperatorsDeleteEvent($operators)
	{
		$eventData = array(
			'line' => $this->id,
			'operators' => $operators
		);
		$event = new \Bitrix\Main\Event('imopenlines', self::EVENT_QUEUE_OPERATORS_DELETE, $eventData);
		$event->send();
	}

	/**
	 * Send event with lists of queue operators from changed line.
	 *
	 * @param $queueBefore
	 * @param $queueAfter
	 */
	private function sendQueueOperatorsChangeEvent($queueBefore, $queueAfter): void
	{
		$eventData = [
			'line' => $this->id,
			'operators_before' => $queueBefore,
			'operators_after' => $queueAfter
		];

		$event = new \Bitrix\Main\Event('imopenlines', self::EVENT_QUEUE_OPERATORS_CHANGE, $eventData);
		$event->send();
	}
}