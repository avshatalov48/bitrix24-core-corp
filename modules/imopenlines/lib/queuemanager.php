<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

use Bitrix\Im\User;

use Bitrix\ImOpenLines\Model\QueueTable;
use Bitrix\ImOpenLines\Model\ConfigQueueTable;

Loc::loadMessages(__FILE__);

/**
 * Class QueueManager
 * @package Bitrix\ImOpenLines
 */
class QueueManager
{
	private $error = null;
	private $idLine = null;

	protected static $instance = [];
	protected static $idIblockStructure = 0;
	protected static $structureDepartments = [];

	public const EVENT_QUEUE_OPERATORS_ADD = 'OnQueueOperatorsAdd';
	public const EVENT_QUEUE_OPERATORS_DELETE = 'OnQueueOperatorsDelete';
	public const EVENT_QUEUE_OPERATORS_CHANGE = 'OnQueueOperatorsChange';

	protected const validQueueTypeField = [
		'user',
		'department'
	];

	/**
	 * @param $fields
	 * @return bool
	 */
	public static function validateQueueFields($fields): bool
	{
		$result = true;

		foreach ($fields as $fieldsEntity)
		{
			if(!(
				!empty($fieldsEntity['ENTITY_ID']) &&
				is_numeric($fieldsEntity['ENTITY_ID']) &&
				$fieldsEntity['ENTITY_ID'] > 0 &&
				self::validateQueueTypeField($fieldsEntity['ENTITY_TYPE'])
			))
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @return bool
	 */
	public static function isEmptyQueueFields($fields): bool
	{
		$result = true;

		if(
			!empty($fields)
			&& is_array($fields)
		)
		{
			foreach ($fields as $fieldsEntity)
			{
				if(
					$result === true
					&& !empty($fieldsEntity['ENTITY_ID'])
				)
				{
					if($fieldsEntity['ENTITY_TYPE'] === 'user')
					{
						$result = false;
					}
					elseif($fieldsEntity['ENTITY_TYPE'] === 'department')
					{
						$usersDepartment = self::getUsersDepartment($fieldsEntity['ENTITY_ID']);
						if($usersDepartment->fetch())
						{
							$result = false;
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $type
	 * @return bool
	 */
	public static function validateQueueTypeField($type): bool
	{
		$result = false;

		if(
			!empty($type) &&
			is_string($type) &&
			in_array($type, self::validQueueTypeField, true)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $configId
	 * @param $fields
	 * @param $numQueue
	 * @return array
	 */
	protected static function getDataAddConfigQueue($configId, $fields, $numQueue): array
	{
		return [
			'SORT' => $numQueue,
			'CONFIG_ID' => $configId,
			'ENTITY_ID' => $fields['ENTITY_ID'],
			'ENTITY_TYPE' => $fields['ENTITY_TYPE'],
		];
	}

	/**
	 * @param $fields
	 * @param $currentFields
	 * @param $numQueue
	 * @return array
	 */
	protected static function getDataUpdateConfigQueue($fields, $currentFields, $numQueue): array
	{
		$result = [];

		$updateFields = [
			'SORT' => $numQueue,
			'ENTITY_ID' => $fields['ENTITY_ID'],
			'ENTITY_TYPE' => $fields['ENTITY_TYPE'],
		];

		$currentFields = [
			'SORT' => $currentFields['SORT'],
			'ENTITY_ID' => $currentFields['ENTITY_ID'],
			'ENTITY_TYPE' => $currentFields['ENTITY_TYPE'],
		];

		if(!empty(array_diff_assoc($updateFields, $currentFields)))
		{
			$result = $updateFields;
		}

		return $result;
	}

	/**
	 * @param $configId
	 * @param $userId
	 * @param $usersFields
	 * @param $numUser
	 * @param int $departmentId
	 * @return array
	 */
	protected static function getDataAddUser($configId, $userId, $usersFields, $numUser, $departmentId = 0): array
	{
		$result = [
			'SORT' => $numUser,
			'CONFIG_ID' => $configId,
			'USER_ID' => $userId,
			'DEPARTMENT_ID' => $departmentId
		];

		if($usersFields !== false)
		{
			if (!empty($usersFields[$userId]['USER_NAME']))
			{
				$result['USER_NAME'] = $usersFields[$userId]['USER_NAME'];
			}
			if (!empty($usersFields[$userId]['USER_WORK_POSITION']))
			{
				$result['USER_WORK_POSITION'] = $usersFields[$userId]['USER_WORK_POSITION'];
			}
			if (!empty($usersFields[$userId]['USER_AVATAR']))
			{
				$result['USER_AVATAR'] = $usersFields[$userId]['USER_AVATAR'];
			}
			if (!empty($usersFields[$userId]['USER_AVATAR_ID']))
			{
				$result['USER_AVATAR_ID'] = $usersFields[$userId]['USER_AVATAR_ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @param $currentUser
	 * @param $usersFields
	 * @param $numUser
	 * @param int $departmentId
	 * @return array
	 */
	protected static function getDataUpdateUser($userId, $currentUser, $usersFields, $numUser, $departmentId = 0): array
	{
		$result = [];

		$dataUpdate = [
			'SORT' => $numUser,
			'USER_ID' => $userId,
			'DEPARTMENT_ID' => $departmentId
		];

		if($usersFields !== false)
		{
			if (!empty($usersFields[$userId]['USER_NAME']))
			{
				$dataUpdate['USER_NAME'] = $usersFields[$userId]['USER_NAME'];
			}
			else
			{
				$dataUpdate['USER_NAME'] = null;
			}

			if (!empty($usersFields[$userId]['USER_WORK_POSITION']))
			{
				$dataUpdate['USER_WORK_POSITION'] = $usersFields[$userId]['USER_WORK_POSITION'];
			}
			else
			{
				$dataUpdate['USER_WORK_POSITION'] = null;
			}
			if (!empty($usersFields[$userId]['USER_AVATAR']))
			{
				$dataUpdate['USER_AVATAR'] = $usersFields[$userId]['USER_AVATAR'];
			}
			else
			{
				$dataUpdate['USER_AVATAR'] = null;
			}
			if (!empty($usersFields[$userId]['USER_AVATAR_ID']))
			{
				$dataUpdate['USER_AVATAR_ID'] = $usersFields[$userId]['USER_AVATAR_ID'];
			}
			else
			{
				$dataUpdate['USER_AVATAR_ID'] = 0;
			}
			if(
				!empty($currentUser['USER_AVATAR_ID']) &&
				(
					empty($dataUpdate['USER_AVATAR_ID']) ||
					$dataUpdate['USER_AVATAR_ID'] != $currentUser['USER_AVATAR_ID']
				)
			)
			{
				\CFile::Delete($currentUser['USER_AVATAR_ID']);
			}
		}

		if($usersFields === false)
		{
			$currentUser = [
				'SORT' => $currentUser['SORT'],
				'USER_ID' => $currentUser['USER_ID'],
				'DEPARTMENT_ID' => $currentUser['DEPARTMENT_ID'],
			];
		}
		else
		{
			$currentUser = [
				'SORT' => $currentUser['SORT'],
				'USER_ID' => $currentUser['USER_ID'],
				'DEPARTMENT_ID' => $currentUser['DEPARTMENT_ID'],
				'USER_NAME' => $currentUser['USER_NAME'],
				'USER_WORK_POSITION' => $currentUser['USER_WORK_POSITION'],
				'USER_AVATAR' => $currentUser['USER_AVATAR'],
				'USER_AVATAR_ID' => $currentUser['USER_AVATAR_ID'],
			];
		}

		if(!empty(array_diff_assoc($dataUpdate, $currentUser)))
		{
			$result = $dataUpdate;
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public static function getIdIblockStructure(): int
	{
		if(empty(self::$idIblockStructure))
		{
			self::$idIblockStructure = (int)Option::get('intranet', 'iblock_structure', 0);
		}
		return self::$idIblockStructure;
	}

	/**
	 * @return array
	 */
	protected static function getStructureDepartments(): array
	{
		if(
			empty(self::$structureDepartments) &&
			Loader::includeModule('iblock')
		)
		{
			$departmentIblockId = self::getIdIblockStructure();

			if($departmentIblockId > 0)
			{
				$raw = \CIBlockSection::GetList(
					['left_margin'=>'asc', 'SORT'=>'ASC'],
					['ACTIVE'=>'Y', 'IBLOCK_ID'=>$departmentIblockId],
					false,
					['ID', 'NAME', 'DEPTH_LEVEL', 'UF_HEAD', 'IBLOCK_SECTION_ID']
				);

				while($row = $raw->GetNext(true, false))
				{
					self::$structureDepartments[$row['ID']] = [
						'id' => (int)$row['ID'],
						'name' => (string)$row['NAME'],
						'depthLevel' => (int)$row['DEPTH_LEVEL'],
						'headUserId' => (int)$row['UF_HEAD'],
						'parent' => (int)$row['IBLOCK_SECTION_ID'],
					];
				}
			}
		}

		return self::$structureDepartments;
	}

	/**
	 * @return true
	 */
	public static function resetCacheStructureDepartments(): bool
	{
		self::$structureDepartments = [];

		return true;
	}

	/**
	 * @param $idDepartment
	 * @param false $recursion
	 * @param false $includeCurrentDepartment
	 * @return array
	 */
	public static function getChildDepartments($idDepartment, $recursion = false, $includeCurrentDepartment = false): array
	{
		$result = [];
		$idDepartment = (int)$idDepartment;

		foreach (self::getStructureDepartments() as $department)
		{
			if($department['parent'] === $idDepartment)
			{
				$result[$department['id']] = $department;
			}
		}

		if(
			$recursion === true &&
			!empty($result)
		)
		{
			foreach ($result as $department)
			{
				$subordinateDepartments = self::getChildDepartments($department['id'], $recursion, false);
				if(!empty($subordinateDepartments))
				{
					foreach ($subordinateDepartments as $id=>$subordinateDepartment)
					{
						$result[$id] = $subordinateDepartment;
					}
				}
			}
		}

		if($includeCurrentDepartment === true)
		{
			$result[$idDepartment] = self::getStructureDepartments()[$idDepartment];
		}

		return $result;
	}

	/**
	 * @param $idDepartment
	 * @param false $recursion
	 * @param false $includeCurrentDepartment
	 * @return array
	 */
	public static function getParentDepartments($idDepartment, $recursion = false, $includeCurrentDepartment = false): array
	{
		$result = [];
		$idDepartment = (int)$idDepartment;

		$structureDepartments = self::getStructureDepartments();
		$currentDepartment = $structureDepartments[$idDepartment];

		foreach ($structureDepartments as $department)
		{
			if($department['id'] === $currentDepartment['parent'])
			{
				$result[$department['id']] = $department;
			}
		}

		if(
			$recursion === true &&
			!empty($result)
		)
		{
			foreach ($result as $department)
			{
				$parentDepartments = self::getParentDepartments($department['id'], $recursion, false);
				if(!empty($parentDepartments))
				{
					foreach ($parentDepartments as $id=>$parentDepartment)
					{
						$result[$id] = $parentDepartment;
					}
				}
			}
		}

		if($includeCurrentDepartment === true)
		{
			$result[$idDepartment] = $currentDepartment;
		}

		return $result;
	}

	/**
	 * @param $idDepartment
	 * @param string[] $select
	 * @param false $excludeHead
	 */
	public static function getUsersDepartment($idDepartment, $select = ['ID'], $excludeHead = true)
	{
		$query = UserTable::query();

		$query->setSelect($select);
		$departments = self::getChildDepartments($idDepartment, true, true);

		$query->addFilter('LOGIC', 'OR');
		foreach ($departments as $id => $department)
		{
			$filter = [
				'UF_DEPARTMENT' => $department['id'],
				'=ACTIVE' => 'Y',
				'!=BLOCKED' => 'Y'
			];
			if(
				$excludeHead === true &&
				$department['headUserId']>0
			)
			{
				$filter['!=ID'] = $department['headUserId'];
			}
			$query->addFilter(null, $filter);
		}

		$query->setCacheTtl(3600);

		$query->setOrder(['ID' => 'asc']);

		return $query->exec();
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public static function isValidUser($userId): bool
	{
		$result = false;

		if(
			$userId > 0 &&
			(
				!Loader::includeModule('im') ||
				(
					!User::getInstance($userId)->isExtranet()
					&& User::getInstance($userId)->isActive()
				)
			)
		)
		{
			$result = true;
		}

		return $result;
	}
	/**
	 * @param $queue
	 * @return array
	 */
	public static function getUsersFromQueue($queue): array
	{
		$result = [];

		if(
			!empty($queue) &&
			is_array($queue)
		)
		{
			foreach ($queue as $entity)
			{
				if(self::validateQueueTypeField($entity['type']))
				{
					if(
						(string)$entity['type'] === 'user' &&
						empty($result[$entity['id']]) &&
						self::isValidUser($entity['id'])
					)
					{
						$result[$entity['id']] = [
							'id' => $entity['id'],
							'type' => 'user',
							'department' => '0'
						];
					}
					elseif((string)$entity['type'] === 'department')
					{
						$usersDepartment = self::getUsersDepartment($entity['id']);
						while ($userId = $usersDepartment->fetch()['ID'])
						{
							if(
								empty($result[$userId]) &&
								self::isValidUser($userId)
							)
							{
								$result[$userId] = [
									'id' => $userId,
									'type' => 'user',
									'department' => $entity['id']
								];
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $queue
	 * @return array
	 */
	public static function getUserListFromQueue($queue): array
	{
		$result = [];
		$users = self::getUsersFromQueue($queue);

		foreach ($users as $user)
		{
			$result[] = $user['id'];
		}

		return $result;
	}

	/**
	 * @param $idLine
	 * @return QueueManager
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getInstance($idLine): QueueManager
	{
		if (
			empty(self::$instance[$idLine]) ||
			!(self::$instance[$idLine] instanceof self)
		)
		{
			self::$instance[$idLine] = new self($idLine);
		}

		return self::$instance[$idLine];
	}

	/**
	 * QueueManager constructor.
	 * @param $idLine
	 */
	public function __construct($idLine)
	{
		$this->error = new BasicError(null, '', '');
		$this->idLine = (int)$idLine;
		Loader::includeModule('im');
	}

	/**
	 * @return array
	 */
	protected function getDefaultUsers(): array
	{
		$result = [];
		$userId = User::getInstance()->getId();

		if(!self::isValidUser($userId))
		{
			$userId = 0;
		}

		if (!empty($userId))
		{
			$data = [
				'CONFIG_ID' => $this->idLine,
				'USER_ID' => $userId,
			];
			$userFields = $this->getUserFields($userId);

			if (!empty($userFields))
			{
				$data = array_merge($data, $userFields);
				$data['USER_AVATAR'] = '';
			}

			$result[] = $data;
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getConfigQueue()
	{
		$result = [];

		$raw = ConfigQueueTable::getList([
			'select' => [
				'ID',
				'SORT',
				'CONFIG_ID',
				'ENTITY_ID',
				'ENTITY_TYPE'
			],
			'filter' => ['=CONFIG_ID' => $this->idLine],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
		]);

		while ($row = $raw->fetch())
		{
			$result[$row['ID']] = $row;
		}

		//TODO: For migration
		if(count($result) === 0)
		{
			$raw = Queue::getList([
				'select' => [
					'SORT',
					'USER_ID'
				],
				'filter' => [
					'=CONFIG_ID' => $this->idLine,
					'=USER.ACTIVE' => 'Y'
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				],
			]);

			while ($row = $raw->fetch())
			{
				$fieldsAdd = [
					'SORT' => $row['SORT'],
					'CONFIG_ID' => $this->idLine,
					'ENTITY_ID' => $row['USER_ID'],
					'ENTITY_TYPE' => 'user',
				];

				$resultAdd = ConfigQueueTable::add($fieldsAdd);
				if($resultAdd->isSuccess())
				{
					$idAdd = $resultAdd->getId();
					$result[$idAdd] = array_merge(['ID' => $idAdd], $fieldsAdd);
				}
			}
		}

		return $result;
	}

	/**
	 * @param $items
	 * @return bool
	 */
	public function deleteItemsConfigQueue($items)
	{
		$result = false;

		$currentItems = $this->getConfigQueue();

		$resultItems = [];
		foreach ($currentItems as $currentItem)
		{
			$delete = false;
			if(!empty($items))
			{
				foreach ($items as $id=>$item)
				{
					if(
						(int)$currentItem['ENTITY_ID'] === (int)$item['ENTITY_ID'] &&
						$currentItem['ENTITY_TYPE'] === $item['ENTITY_TYPE']
					)
					{
						$delete = true;
						unset($items[$id]);
						$result = true;
					}
				}
			}
			if($delete === false)
			{
				$resultItems[] = [
					'ENTITY_ID' => $currentItem['ENTITY_ID'],
					'ENTITY_TYPE' => $currentItem['ENTITY_TYPE'],
				];
			}
		}

		$this->update($resultItems);

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function refresh()
	{
		$currentItems = $this->getConfigQueue();
		$resultItems = [];
		foreach ($currentItems as $currentItem)
		{
			$resultItems[] = [
				'ENTITY_ID' => $currentItem['ENTITY_ID'],
				'ENTITY_TYPE' => $currentItem['ENTITY_TYPE'],
			];
		}

		$this->update($resultItems);

		return true;
	}

	/**
	 * @param $fields
	 * @param array|false $usersFields
	 * @return bool
	 */
	public function compatibleUpdate($fields, $usersFields = false)
	{
		foreach ($fields as $cell=>$field)
		{
			if(!is_array($field))
			{
				$fields[$cell] = [
					'ENTITY_TYPE' => 'user',
					'ENTITY_ID' => $field
				];
			}
		}

		$resultUpdate = $this->update($fields, $usersFields);

		return $resultUpdate->isSuccess();
	}

	/**
	 * @param $fields
	 * @param array|false $usersFields
	 * @return Result
	 */
	public function update($fields, $usersFields = false): Result
	{
		$result = new Result();

		foreach ($fields as $cell => $fieldsEntity)
		{
			if(
				$fieldsEntity['ENTITY_TYPE'] === 'user' &&
				!self::isValidUser($fieldsEntity['ENTITY_ID'])
			)
			{
				unset($fields[$cell], $usersFields[$fieldsEntity['ENTITY_ID']]);
			}
		}

		if(self::validateQueueFields($fields))
		{
			$taggedCache = Application::getInstance()->getTaggedCache();

			$queueUsersBefore = [];
			$queueUsersAfter = [];

			$addUsers = [];
			$updateUsers = [];
			$deleteUsers = [];

			$addQueue = [];
			$updateQueue = [];
			$deleteQueue = [];

			$usersRaw = QueueTable::getList([
				'filter' => [
					'=CONFIG_ID' => $this->idLine
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				]
			]);
			while ($user = $usersRaw->fetch())
			{
				$currentUsers[$user['USER_ID']] = $user;
				$queueUsersBefore[] = $user['USER_ID'];
				$deleteUsers[$user['USER_ID']] = $user;
			}

			$configQueueRaw = ConfigQueueTable::getList([
				'filter' => [
					'=CONFIG_ID' => $this->idLine
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				]
			]);
			while ($configQueue = $configQueueRaw->fetch())
			{
				$currentConfigQueue[$configQueue['ENTITY_TYPE']][$configQueue['ENTITY_ID']] = $configQueue;
				$deleteQueue[$configQueue['ENTITY_TYPE']][$configQueue['ENTITY_ID']] = $configQueue;
			}

			$numUser = 0;
			$numQueue = 0;
			$newUsers = [];
			foreach ($fields as $fieldsEntity)
			{
				if(!empty($currentConfigQueue[$fieldsEntity['ENTITY_TYPE']][$fieldsEntity['ENTITY_ID']]))
				{
					unset($deleteQueue[$fieldsEntity['ENTITY_TYPE']][$fieldsEntity['ENTITY_ID']]);

					$updateFields = self::getDataUpdateConfigQueue($fieldsEntity, $currentConfigQueue[$fieldsEntity['ENTITY_TYPE']][$fieldsEntity['ENTITY_ID']], $numQueue);
					if(!empty($updateFields))
					{
						$updateQueue[$currentConfigQueue[$fieldsEntity['ENTITY_TYPE']][$fieldsEntity['ENTITY_ID']]['ID']] = $updateFields;
					}
				}
				else
				{
					$addQueue[] = self::getDataAddConfigQueue($this->idLine, $fieldsEntity, $numQueue);
				}

				if($fieldsEntity['ENTITY_TYPE'] === 'user')
				{
					if(empty($newUsers[$fieldsEntity['ENTITY_ID']]))
					{
						$queueUsersAfter[] = $fieldsEntity['ENTITY_ID'];

						if(!empty($currentUsers[$fieldsEntity['ENTITY_ID']]))
						{
							unset($deleteUsers[$fieldsEntity['ENTITY_ID']]);

							$updateFields = self::getDataUpdateUser($fieldsEntity['ENTITY_ID'], $currentUsers[$fieldsEntity['ENTITY_ID']], $usersFields, $numUser);

							if(!empty($updateFields))
							{
								$updateUsers[$currentUsers[$fieldsEntity['ENTITY_ID']]['ID']] = $updateFields;
							}
						}
						else
						{
							$addUsers[] = self::getDataAddUser($this->idLine, $fieldsEntity['ENTITY_ID'], $usersFields, $numUser);
						}
					}

					$newUsers[$fieldsEntity['ENTITY_ID']] = true;
					$numUser++;
				}
				elseif($fieldsEntity['ENTITY_TYPE'] === 'department')
				{
					$usersDepartment = self::getUsersDepartment($fieldsEntity['ENTITY_ID']);

					while ($userId = $usersDepartment->fetch()['ID'])
					{
						if(self::isValidUser($userId))
						{
							if(empty($newUsers[$userId]))
							{
								$queueUsersAfter[] = $userId;

								if(!empty($currentUsers[$userId]))
								{
									unset($deleteUsers[$userId]);

									$updateFields = self::getDataUpdateUser($userId, $currentUsers[$userId], $usersFields, $numUser, $fieldsEntity['ENTITY_ID']);

									if(!empty($updateFields))
									{
										$updateUsers[$currentUsers[$userId]['ID']] = $updateFields;
									}
								}
								else
								{
									$addUsers[] = self::getDataAddUser($this->idLine, $userId, $usersFields, $numUser, $fieldsEntity['ENTITY_ID']);
								}
							}

							$newUsers[$userId] = true;
							$numUser++;
						}
					}
				}

				$numQueue++;
			}

			if(empty($fields))
			{
				$addUsers = $this->getDefaultUsers();

				if(!empty($addUsers))
				{
					foreach ($addUsers as $addUser)
					{
						$addQueue[] = self::getDataAddConfigQueue($this->idLine, ['ENTITY_TYPE' => 'user', 'ENTITY_ID' => $addUser['USER_ID']], $numQueue);
						$numQueue++;
					}
				}
			}

			if(!empty($deleteQueue))
			{
				foreach ($deleteQueue as $typeEntity)
				{
					if(!empty($typeEntity))
					{
						foreach ($typeEntity as $entity)
						{
							ConfigQueueTable::delete($entity['ID']);
						}
						$result->setResult(true);
					}
				}
			}
			if(!empty($updateQueue))
			{
				foreach ($updateQueue as $id => $queue)
				{
					ConfigQueueTable::update($id, $queue);
				}
				$result->setResult(true);
			}
			if(!empty($addQueue))
			{
				foreach ($addQueue as $queue)
				{
					ConfigQueueTable::add($queue);
				}
				$result->setResult(true);
			}

			if(!empty($deleteUsers))
			{
				foreach ($deleteUsers as $user)
				{
					QueueTable::delete($user['ID']);
				}
				$result->setResult(true);
			}
			if(!empty($updateUsers))
			{
				foreach ($updateUsers as $id => $user)
				{
					QueueTable::update($id, $user);
				}
				$result->setResult(true);
			}
			if(!empty($addUsers))
			{
				foreach ($addUsers as $user)
				{
					QueueTable::add($user);
				}
				$result->setResult(true);
			}

			$this->sendQueueChangeEvents($queueUsersBefore, $queueUsersAfter);
		}
		else
		{
			$result->addError(new Error('Invalid fields describing queue entities were passed', 'IMOL_ERROR_INCORRECT_FIELDS', __METHOD__, $fields));
		}

		return $result;
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
		$fields = [];
		$user = User::getInstance($userId);

		if ((int)$user->getId() === (int)$userId)
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

	/**
	 * @return BasicError|null
	 */
	public function getError():?BasicError
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
		$eventData = [
			'line' => $this->idLine,
			'operators' => $operators
		];
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
		$eventData = [
			'line' => $this->idLine,
			'operators' => $operators
		];
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
			'line' => $this->idLine,
			'operators_before' => $queueBefore,
			'operators_after' => $queueAfter
		];

		$event = new \Bitrix\Main\Event('imopenlines', self::EVENT_QUEUE_OPERATORS_CHANGE, $eventData);
		$event->send();
	}

	/**
	 * @deprecated
	 *
	 * @param $users
	 * @param array|false $usersFields
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function updateUsers($users, $usersFields = false)
	{
		return $this->compatibleUpdate($users, $usersFields);
	}
}