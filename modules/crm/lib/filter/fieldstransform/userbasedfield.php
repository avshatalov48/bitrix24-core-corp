<?php

namespace Bitrix\Crm\Filter\FieldsTransform;

use Bitrix\Main\Engine\CurrentUser;

/**
 * Transform 'all-users' and 'other-users' filter values to ORM compatible values.
 */
final class UserBasedField
{

	public const DEFAULT_FILTER_FIELDS_TO_TRANSFORM = [
		'ASSIGNED_BY_ID',
		'ACTIVITY_RESPONSIBLE_IDS'
	];

	private const KEEP_ALL_USERS_IN_FILTER = [
		'ACTIVITY_RESPONSIBLE_IDS'
	];

	/**
	 * Transform 'all-users' and 'other-users' filter values to ORM compatible values.
	 * @param array $filter
	 * @param array|null $fieldNames fields to transform. If empty will be user default list
	 * 			self::DEFAULT_FILTER_FIELDS_TO_TRANSFORM
	 * @return void
	 */
	public static function applyTransformWrapper(array &$filter, ?array $fieldNames = null)
	{
		if ($fieldNames === null)
		{
			$fieldNames = self::DEFAULT_FILTER_FIELDS_TO_TRANSFORM;
		}

		$currentUser = CurrentUser::get()->getId();

		$instance = new self();

		$instance->transformAll($filter, $fieldNames, $currentUser);
	}

	/**
	 * @param array $filter
	 * @param string[] $fieldNames
	 * @param int|null $currentUser
	 * @return void
	 */
	public function transformAll(array &$filter, array $fieldNames, ?int $currentUser): void
	{
		foreach ($fieldNames as $fieldName)
		{
			$this->transform($filter, $fieldName, $currentUser);
		}
	}


	public function transform(array &$filter, string $fieldName, ?int $currentUser): void
	{
		if (!isset($filter[$fieldName]))
		{
			return;
		}

		if (!is_array($filter[$fieldName]))
		{
			return;
		}

		if ($this->isAllUsers($filter[$fieldName], $currentUser))
		{
			$filter = $this->allUsers($fieldName, $filter);
		}
		elseif ($this->isOtherUsers($filter[$fieldName], $currentUser))
		{
			$name = '!' . $fieldName;
			$filter[$name] = $currentUser;
			unset($filter[$fieldName]);
		}
	}

	private function isCurrentUserInFilter(array $assignedField, ?int $currentUser): bool
	{
		return $currentUser && in_array($currentUser, $assignedField);
	}

	private function isAllUsers(array $assignedFilter, ?int $currentUser): bool
	{
		if (in_array('all-users', $assignedFilter, true))
		{
			return true;
		}

		if (
			in_array('other-users', $assignedFilter, true)
			&& $this->isCurrentUserInFilter($assignedFilter, $currentUser)
		)
		{
			return true;
		}

		return false;
	}

	private function isOtherUsers(array $assignedFilter, ?int $currentUser): bool
	{
		return (
			in_array('other-users', $assignedFilter, true)
			&& !$this->isCurrentUserInFilter($assignedFilter, $currentUser)
		);
	}

	public function allUsers(string $fieldName, array $filter): array
	{
		if (!in_array($fieldName, self::KEEP_ALL_USERS_IN_FILTER))
		{
			unset($filter[$fieldName]);
		}
		else
		{
			$filter[$fieldName] = [];
		}
		return $filter;
	}
}