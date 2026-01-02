<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\SocialNetwork\GroupProvider;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Integration\SocialNetwork;

class PrepareMembers implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		$creatorId = (int)($fields['CREATED_BY'] ?? 0);

		if ($creatorId > 0)
		{
			$fields['CREATED_BY'] = $creatorId;
		}

		if ($this->isResponsibleChanged($fields))
		{
			$fields = $this->prepareNewResponsible($fields, $fullTaskData);
		}

		$this->checkExtranet($fields, $fullTaskData);

		$fields = $this->castMembers('ACCOMPLICES', $fields);
		return $this->castMembers('AUDITORS', $fields);
	}

	private function prepareNewResponsible(array $fields, array $fullTaskData): array
	{
		$this->checkNewResponsible($fields, $fullTaskData);

		$newResponsibleId = (int)$fields['RESPONSIBLE_ID'];

		$currentResponsible = (int)$fullTaskData['RESPONSIBLE_ID'];

		if ($currentResponsible !== $newResponsibleId)
		{
			$user = $this->getUser($newResponsibleId);

			$fields = $this->castReport($fields, $user);
			$fields = $this->castStatus($fields);

			$fields['DECLINE_REASON'] = false;
		}

		return $fields;
	}

	private function checkNewResponsible(array $fields, array $fullTaskData): void
	{
		$newResponsibleId = (int)$fields['RESPONSIBLE_ID'];

		if ($newResponsibleId === $this->config->getUserId())
		{
			return;
		}

		if (User::isSuper($this->config->getUserId()))
		{
			return;
		}

		if (!Extranet\User::isExtranet($this->config->getUserId()))
		{
			return;
		}

		if (
			!isset($fields['GROUP_ID'])
			&& $this->isGroupRemoved($fields, $fullTaskData)
			&& !$this->isUserInGroup($newResponsibleId, $fullTaskData)
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
		}

		if (
			$this->isResponsibleAndGroupChanged($fields, $fullTaskData)
			&& !$this->isUserInGroup($newResponsibleId, $fields)
		)
		{

			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
		}

		if (
			$this->isGroupAdded($fields, $fullTaskData)
			&& !$this->isUserInGroup($newResponsibleId, $fullTaskData)
		)
		{
			$isCollab = GroupProvider::isCollab($fields['GROUP_ID']);
			$messageKey = $isCollab ? 'TASKS_BAD_ASSIGNEE_IN_COLLAB' : 'TASKS_BAD_ASSIGNEE_IN_GROUP';

			throw new TaskFieldValidateException(Loc::getMessage($messageKey));
		}
	}

	private function isResponsibleAndGroupChanged(array $fields, array $fullTaskData): bool
	{
		if ((int)$fields['GROUP_ID'] === 0)
		{
			return false;
		}

		if (!isset($fields['GROUP_ID']))
		{
			return false;
		}

		return (int)$fields['RESPONSIBLE_ID'] !== (int)$fullTaskData['RESPONSIBLE_ID'];
	}

	private function isGroupAdded(array $fields, array $fullTaskData): bool
	{
		if ((int)$fields['GROUP_ID'] === 0)
		{
			return false;
		}

		return !isset($fullTaskData['GROUP_ID']);
	}

	private function isGroupRemoved(array $fields, array $fullTaskData): bool
	{
		if (!isset($fullTaskData['GROUP_ID']))
		{
			return false;
		}

		if ((int)$fullTaskData['GROUP_ID'] === 0)
		{
			return false;
		}

		return !isset($fields['GROUP_ID']);
	}

	private function isUserInGroup(int $userId, array $data): bool
	{
		$responsibleRoleInGroup = SocialNetwork\User::getUserRole(
			$userId, [$data['GROUP_ID']]
		);

		return isset($responsibleRoleInGroup[$fullTaskData['GROUP_ID']]);
	}

	private function isResponsibleChanged(array $fields): bool
	{
		return isset($fields['RESPONSIBLE_ID']);
	}

	private function getUser(int $userId): array
	{
		if ($userId === $this->config->getUserId())
		{
			$userResult = \CUser::GetByID($userId);
		}
		else
		{
			$by = 'id';
			$order = 'asc';
			$filter = ['ID_EQUAL_EXACT' => $userId];
			$parameters = [
				'FIELDS' => ['ID'],
				'SELECT' => ['UF_DEPARTMENT'],
			];

			$userResult = \CUser::GetList($by, $order, $filter, $parameters);
		}

		$user = $userResult->Fetch();
		if (!$user)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_EX'));
		}

		return $user;
	}

	private function castMembers(string $fieldName, array $fields): array
	{
		if (!isset($fields[$fieldName]) || !is_array($fields[$fieldName]))
		{
			return $fields;
		}

		$members = $fields[$fieldName];

		Collection::normalizeArrayValuesByInt($members, false);

		$fields[$fieldName] = $members;

		return $fields;
	}

	private function checkExtranet(array $fields, array $fullTaskData): void
	{
		if (!isset($fields['GROUP_ID']))
		{
			return;
		}

		if ($fields['GROUP_ID'] === 0)
		{
			return;
		}

		if (!isset($fields['RESPONSIBLE_ID']))
		{
			return;
		}

		if (User::isSuper($this->config->getUserId()))
		{
			return;
		}

		if (!Extranet\User::isExtranet($this->config->getUserId()))
		{
			return;
		}

		$responsibleRoleInGroup = SocialNetwork\User::getUserRole(
			$fullTaskData['RESPONSIBLE_ID'],
			[$fields['GROUP_ID']]
		);

		if (isset($responsibleRoleInGroup[$fields['GROUP_ID']]))
		{
			return;
		}

		throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
	}

	private function castReport(array $fields, array $user): array
	{
		$subordinateDepartments = Department::getSubordinateIds(
			$fields['CREATED_BY'] ?? null,
			true
		);

		$userDepartment = $user['UF_DEPARTMENT'];
		$userDepartment = (is_array($userDepartment) ? $userDepartment : [$userDepartment]);

		$isSubordinate = (count(array_intersect($subordinateDepartments, $userDepartment)) > 0);

		if (!$isSubordinate)
		{
			$fields['ADD_IN_REPORT'] = 'N';
		}

		return $fields;
	}

	private function castStatus(array $fields): array
	{
		if (
			!isset($fields['STATUS'])
			|| !$fields['STATUS']
		)
		{
			$fields['STATUS'] = Status::PENDING;
		}

		return $fields;
	}
}