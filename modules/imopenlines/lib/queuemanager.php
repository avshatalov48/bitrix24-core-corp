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
				$data = array(
					"CONFIG_ID" => $this->id,
					"USER_ID" => $userId,
				);
				$userFields = $this->getUserFields($userId);

				if (!empty($userFields))
				{
					$data = array_merge($data, $userFields);
				}

				QueueTable::add($data);
			}
		}

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

			if (substr($avatar, 0, 1) == '/')
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
}