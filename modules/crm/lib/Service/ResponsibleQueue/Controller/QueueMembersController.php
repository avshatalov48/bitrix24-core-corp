<?php

namespace Bitrix\Crm\Service\ResponsibleQueue\Controller;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\HumanResources\DepartmentQueries;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ResponsibleQueue\Entity\QueueConfigMembersTable;
use Bitrix\Crm\Service\ResponsibleQueue\Entity\QueueTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class QueueMembersController
{
	use Singleton;

	private const VALID_QUEUE_TYPE_FIELD = [
		'user',
		'department',
	];

	public function getList(int $queueConfigId): array
	{
		return [];
	}

	public function update(int $queueConfigId, array $members): Result
	{
		$result = new Result();

		$members = $this->filterMembers($members);
		if ($this->isQueueMembersFieldsValid($members))
		{
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
					'=CONFIG_ID' => $queueConfigId,
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC',
				]
			]);
			while ($user = $usersRaw->fetch())
			{
				$currentUsers[$user['USER_ID']] = $user;
				$queueUsersBefore[] = $user['USER_ID'];
				$deleteUsers[$user['USER_ID']] = $user;
			}

			$configQueueRaw = QueueConfigMembersTable::getList([
				'filter' => [
					'=QUEUE_CONFIG_ID' => $queueConfigId,
				],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC',
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
			foreach ($members as $row)
			{
				if (!empty($currentConfigQueue[$row['ENTITY_TYPE']][$row['ENTITY_ID']]))
				{
					unset($deleteQueue[$row['ENTITY_TYPE']][$row['ENTITY_ID']]);

					$updateFields = $this->getDataUpdateConfigQueue(
						$row,
						$currentConfigQueue[$row['ENTITY_TYPE']][$row['ENTITY_ID']],
						$numQueue
					);
					if (!empty($updateFields))
					{
						$updateQueue[$currentConfigQueue[$row['ENTITY_TYPE']][$row['ENTITY_ID']]['ID']] = $updateFields;
					}
				}
				else
				{
					$addQueue[] = $this->getDataAddConfigQueue($queueConfigId, $numQueue, $row);
				}

				if ($row['ENTITY_TYPE'] === 'user')
				{
					if (empty($newUsers[$row['ENTITY_ID']]))
					{
						$queueUsersAfter[] = $row['ENTITY_ID'];

						if (!empty($currentUsers[$row['ENTITY_ID']]))
						{
							unset($deleteUsers[$row['ENTITY_ID']]);

							$updateFields = $this->getDataUpdateUser(
								$row['ENTITY_ID'],
								$numUser,
								$currentUsers[$row['ENTITY_ID']]
							);

							if (!empty($updateFields))
							{
								$updateUsers[$currentUsers[$row['ENTITY_ID']]['ID']] = $updateFields;
							}
						}
						else
						{
							$addUsers[] = $this->getDataAddUser($queueConfigId, $row['ENTITY_ID'], $numUser);
						}
					}

					$newUsers[$row['ENTITY_ID']] = true;
					$numUser++;
				}
				elseif ($row['ENTITY_TYPE'] === 'department')
				{
					$usersDepartment = $this->getDepartmentUsersList($row['ENTITY_ID']);
					foreach ($usersDepartment as $userId)
					{
						if ($this->isValidUser($userId))
						{
							if (empty($newUsers[$userId]))
							{
								$queueUsersAfter[] = $userId;

								if (!empty($currentUsers[$userId]))
								{
									unset($deleteUsers[$userId]);

									$updateFields = $this->getDataUpdateUser(
										$userId,
										$numUser,
										$currentUsers[$userId],
										$row['ENTITY_ID']
									);

									if (!empty($updateFields))
									{
										$updateUsers[$currentUsers[$userId]['ID']] = $updateFields;
									}
								}
								else
								{
									$addUsers[] = $this->getDataAddUser(
										$queueConfigId,
										$userId,
										$numUser,
										$row['ENTITY_ID']
									);
								}
							}

							$newUsers[$userId] = true;
							$numUser++;
						}
					}
				}

				$numQueue++;
			}

			if (empty($members))
			{
				$addUsers = $this->getDefaultUsers($queueConfigId);
				if (!empty($addUsers))
				{
					foreach ($addUsers as $addUser)
					{
						$addQueue[] = $this->getDataAddConfigQueue(
							$queueConfigId,
							$numQueue,
							[
								'ENTITY_TYPE' => 'user',
								'ENTITY_ID' => $addUser['USER_ID']]
						);
						$numQueue++;
					}
				}
			}

			if (!empty($deleteQueue))
			{
				foreach ($deleteQueue as $typeEntity)
				{
					if (!empty($typeEntity))
					{
						foreach ($typeEntity as $entity)
						{
							QueueConfigMembersTable::delete($entity['ID']);
						}
					}
				}
			}
			if (!empty($updateQueue))
			{
				foreach ($updateQueue as $id => $queue)
				{
					QueueConfigMembersTable::update($id, $queue);
				}
			}
			if (!empty($addQueue))
			{
				foreach ($addQueue as $queue)
				{
					QueueConfigMembersTable::add($queue);
				}
			}

			if (!empty($deleteUsers))
			{
				foreach ($deleteUsers as $user)
				{
					QueueTable::delete($user['ID']);
				}
			}

			if (!empty($updateUsers))
			{
				foreach ($updateUsers as $id => $user)
				{
					QueueTable::update($id, $user);
				}
			}

			if (!empty($addUsers))
			{
				foreach ($addUsers as $user)
				{
					QueueTable::add($user);
				}
			}

			// @todo
			// @see imopenlines/lib/queuemanager.php => sendQueueChangeEvents()
			//$this->sendQueueChangeEvents($queueUsersBefore, $queueUsersAfter);
		}
		else
		{
			$result->addError(
				new Error(
					'Invalid fields describing queue entities were passed',
					ErrorCode::INVALID_ARG_VALUE
				)
			);
		}

		return $result;
	}

	private function filterMembers(array $members): array
	{
		foreach ($members as $index => $row)
		{
			if (
				$row['ENTITY_TYPE'] === 'user'
				&& !$this->isValidUser($row['ENTITY_ID'])
			)
			{
				unset($members[$index]);
			}

			if (
				$row['ENTITY_TYPE'] === 'department'
				&& !$this->isValidDepartment($row['ENTITY_ID'])
			)
			{
				unset($members[$index]);
			}
		}

		return array_values($members);
	}

	private function getDepartmentUsersList(int $departmentId): array
	{
		$subDepartments = DepartmentQueries::getInstance()
			->getSubDepartmentsAccessCodesIds($departmentId)
		;

		return DepartmentQueries::getInstance()->queryUserIdsByDepartments(
			[...[$departmentId], ...$subDepartments],
			true
		);
	}

	private function isQueueMembersFieldsValid(array $members): bool
	{
		$result = true;

		foreach ($members as $row)
		{
			if (!(
				!empty($row['ENTITY_ID'])
				&& is_numeric($row['ENTITY_ID'])
				&& $row['ENTITY_ID'] > 0
				&& $this->isTypeFieldValid($row['ENTITY_TYPE'])
			))
			{
				$result = false;

				break;
			}
		}

		return $result;
	}

	private function isTypeFieldValid(mixed $type): bool
	{
		return
			!empty($type)
			&& is_string($type)
			&& in_array($type, self::VALID_QUEUE_TYPE_FIELD, true)
		;
	}

	private function isValidUser(int $userId): bool
	{
		// @todo: add validation rules
		return $userId > 0;
	}

	private function isValidDepartment(int $departmentId): bool
	{
		$usersDepartment = $this->getDepartmentUsersList($departmentId);
		if (empty($usersDepartment))
		{
			return false;
		}

		foreach ($usersDepartment as $index => $userId)
		{
			if (!$this->isValidUser($userId))
			{
				unset($usersDepartment[$index]);
			}
		}

		return !empty($usersDepartment);
	}

	private function getDataUpdateConfigQueue(array $members, array $currentFields, int $numQueue): array
	{
		$result = [];

		$updateFields = [
			'SORT' => $numQueue,
			'ENTITY_ID' => $members['ENTITY_ID'],
			'ENTITY_TYPE' => $members['ENTITY_TYPE'],
		];

		$currentFields = [
			'SORT' => $currentFields['SORT'],
			'ENTITY_ID' => $currentFields['ENTITY_ID'],
			'ENTITY_TYPE' => $currentFields['ENTITY_TYPE'],
		];

		if (!empty(array_diff_assoc($updateFields, $currentFields)))
		{
			$result = $updateFields;
		}

		return $result;
	}

	private function getDataAddConfigQueue(int $configId, int $numQueue, array $fields): array
	{
		return [
			'SORT' => $numQueue,
			'QUEUE_CONFIG_ID' => $configId,
			'ENTITY_ID' => $fields['ENTITY_ID'],
			'ENTITY_TYPE' => $fields['ENTITY_TYPE'],
		];
	}

	private function getDataAddUser(int $configId, int $userId, int $numUser, int $departmentId = 0): array
	{
		return [
			'SORT' => $numUser,
			'CONFIG_ID' => $configId,
			'USER_ID' => $userId,
			'DEPARTMENT_ID' => $departmentId,
			'SETTINGS' => [],
		];
	}

	private function getDataUpdateUser(int $userId, int $numUser, array $currentUser, int $departmentId = 0): array
	{
		$result = [];

		$dataUpdate = [
			'SORT' => $numUser,
			'USER_ID' => $userId,
			'DEPARTMENT_ID' => $departmentId
		];

		$currentUser = [
			'SORT' => $currentUser['SORT'],
			'USER_ID' => $currentUser['USER_ID'],
			'DEPARTMENT_ID' => $currentUser['DEPARTMENT_ID'],
		];

		if (!empty(array_diff_assoc($dataUpdate, $currentUser)))
		{
			$result = $dataUpdate;
		}

		return $result;
	}

	private function getDefaultUsers(int $queueConfigId): array
	{
		$result = [];
		$userId = Container::getInstance()->getContext()->getUserId();
		if (!$this->isValidUser($userId))
		{
			$userId = 0;
		}

		if (!empty($userId))
		{
			$data = [
				'CONFIG_ID' => $queueConfigId,
				'USER_ID' => $userId,
				'SETTINGS' => [],
			];
			$result[] = $data;
		}

		return $result;
	}
}
