<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\SocialNetwork\GroupProvider;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Integration\SocialNetwork;

class PrepareMembers implements PrepareFieldInterface
{
	use ConfigTrait;

	/**
	 * @throws TaskFieldValidateException
	 */
	public function __invoke(array $fields): array
	{
		$creatorId = (int)($fields['CREATED_BY'] ?? 0);
		$responsibleId = (int)($fields['RESPONSIBLE_ID'] ?? 0);

		if ($creatorId <= 0)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_CREATED_BY'));
		}

		if ($responsibleId <= 0)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_ID'));
		}

		$fields['CREATED_BY'] = $creatorId;
		$fields['RESPONSIBLE_ID'] = $responsibleId;

		$user = $this->getUser($responsibleId);

		$this->checkExtranet($fields);

		$fields = $this->castReport($fields, $user);

		$fields = $this->castMembers('ACCOMPLICES', $fields);

		$fields = $this->castMembers('AUDITORS', $fields);

		// todo
		$fields['DECLINE_REASON'] = false;

		return $fields;
	}

	/**
	 * @throws TaskFieldValidateException
	 */
	private function checkExtranet(array $fields): void
	{
		$responsibleId = $fields['RESPONSIBLE_ID'];
		$groupId = $fields['GROUP_ID'];
		$userId = $this->config->getUserId();

		if ($groupId <= 0)
		{
			return;
		}

		if ($responsibleId === $userId)
		{
			return;
		}

		if (User::isSuper($userId))
		{
			return;
		}

		if (!Extranet\User::isExtranet($userId))
		{
			return;
		}

		$responsibleRoleInGroup = SocialNetwork\User::getUserRole(
			$responsibleId, [$fields['GROUP_ID']]
		);

		if (isset($responsibleRoleInGroup[$fields['GROUP_ID']]))
		{
			return;
		}

		$messageKey = GroupProvider::isCollab($fields['GROUP_ID'])
			? 'TASKS_BAD_ASSIGNEE_IN_COLLAB'
			: 'TASKS_BAD_ASSIGNEE_IN_GROUP';

		throw new TaskFieldValidateException(Loc::getMessage($messageKey));
	}

	private function castReport(array $fields, array $user): array
	{
		$subordinateDepartments = Department::getSubordinateIds(
			$fields['CREATED_BY'],
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
			$fields[$fieldName] = [];
		}

		$members = $fields[$fieldName];

		Collection::normalizeArrayValuesByInt($members, false);

		$fields[$fieldName] = $members;

		return $fields;
	}
}