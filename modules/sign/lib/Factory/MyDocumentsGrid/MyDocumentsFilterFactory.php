<?php

namespace Bitrix\Sign\Factory\MyDocumentsGrid;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Item\MyDocumentsGrid\MyDocumentsFilter;
use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;

class MyDocumentsFilterFactory
{
	public const ROLE = 'ROLE';
	public const INITIATOR = 'INITIATOR';
	public const EDITOR = 'EDITOR';
	public const REVIEWER = 'REVIEWER';
	public const SIGNER = 'SIGNER';
	public const COMPANY = 'COMPANY';
	public const STATUS = 'STATUS';
	public const DATE_MODIFY = 'DATE_MODIFY';

	public function createFromRequestFilterOptions(int $currentUserId, array $requestOptionsFilter): ?MyDocumentsFilter
	{
		$role = $this->getRoleFromRequest($requestOptionsFilter);
		$initiators = $this->getInitiatorsFromRequest($currentUserId, $requestOptionsFilter, $role);
		$editors = $this->getEditorsFromRequest($currentUserId, $requestOptionsFilter, $role);
		$reviewers = $this->getReviewersFromRequest($currentUserId, $requestOptionsFilter, $role);
		$signers = $this->getSignersFromRequest($currentUserId, $requestOptionsFilter, $role);
		$assignees = $this->getAssigneesFromRequest($currentUserId, $requestOptionsFilter, $role);
		$companies = $this->getIntArrayFromRequest($requestOptionsFilter, static::COMPANY);
		$statuses = $this->getStatusesFromRequest($requestOptionsFilter);
		$dateModifyFrom = $this->getDateModifyFrom($requestOptionsFilter);
		$dateModifyTo = $this->getDateModifyTo($requestOptionsFilter);
		$text = (string)($requestOptionsFilter['FIND'] ?? '');
		$assigneesOrSigners = $this->getAssigneesOrSignersFromRequest($requestOptionsFilter, $role);

		$filter = new MyDocumentsFilter(
			role: $role,
			initiators: $initiators,
			editors: $editors,
			reviewers: $reviewers,
			signers: $signers,
			assignees: $assignees,
			companies: $companies,
			statuses: $statuses,
			dateModifyFrom: $dateModifyFrom,
			dateModifyTo: $dateModifyTo,
			text: $text,
			assigneesOrSigners: $assigneesOrSigners,
		);

		return $filter->isEmpty() ? null : $filter;
	}

	private function getRoleFromRequest(array $requestOptionsFilter): ?ActorRole
	{
		return ActorRole::tryFrom((string)($requestOptionsFilter[static::ROLE] ?? ''));
	}

	/**
	 * @param int $currentUserId
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getInitiatorsFromRequest(int $currentUserId, array $requestOptionsFilter, ?ActorRole $role): array
	{
		$userIds = $this->getIntArrayFromRequest($requestOptionsFilter, static::INITIATOR);
		if ($role === ActorRole::INITIATOR)
		{
			$userIds[] = $currentUserId;
		}

		return array_values(array_unique($userIds));
	}

	/**
	 * @param int $currentUserId
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getEditorsFromRequest(int $currentUserId, array $requestOptionsFilter, ?ActorRole $role): array
	{
		$userIds = $this->getIntArrayFromRequest($requestOptionsFilter, static::EDITOR);
		if ($role === ActorRole::EDITOR)
		{
			$userIds[] = $currentUserId;
		}

		return array_values(array_unique($userIds));
	}

	/**
	 * @param int $currentUserId
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getReviewersFromRequest(int $currentUserId, array $requestOptionsFilter, ?ActorRole $role): array
	{
		$userIds = $this->getIntArrayFromRequest($requestOptionsFilter, static::REVIEWER);
		if ($role === ActorRole::REVIEWER)
		{
			$userIds[] = $currentUserId;
		}

		return array_values(array_unique($userIds));
	}

	/**
	 * @param array $requestOptionsFilter
	 * @param string $key
	 *
	 * @return list<int>
	 */
	private function getIntArrayFromRequest(array $requestOptionsFilter, string $key): array
	{
		$raw = (array)($requestOptionsFilter[$key] ?? []);

		$ints = [];
		foreach ($raw as $value)
		{
			if ($value > 0 && is_numeric($value) && (int)$value == $value)
			{
				$ints[] = (int)$value;
			}
		}

		return $ints;
	}

	/**
	 * @param int $currentUserId
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getSignersFromRequest(int $currentUserId, array $requestOptionsFilter, ?ActorRole $role): array
	{
		if ($role === ActorRole::SIGNER || $role === ActorRole::INITIATOR)
		{
			return [$currentUserId];
		}

		if ($role === ActorRole::ASSIGNEE)
		{
			return $this->getIntArrayFromRequest($requestOptionsFilter, static::SIGNER);
		}

		return [];
	}

	/**
	 * @param int $currentUserId
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getAssigneesFromRequest(int $currentUserId, array $requestOptionsFilter, ?ActorRole $role): array
	{
		if ($role === ActorRole::ASSIGNEE)
		{
			return [$currentUserId];
		}

		if ($role == ActorRole::SIGNER)
		{
			return $this->getIntArrayFromRequest($requestOptionsFilter, static::SIGNER);
		}

		return [];
	}

	/**
	 * @param array $requestOptionsFilter
	 *
	 * @return list<FilterStatus>
	 */
	private function getStatusesFromRequest(array $requestOptionsFilter): array
	{
		$raw = (array)($requestOptionsFilter[static::STATUS] ?? []);

		$statuses = [];
		foreach ($raw as $value)
		{
			$status = FilterStatus::tryFrom((string)$value);
			if ($status)
			{
				$statuses[] = $status;
			}
		}

		return $statuses;
	}

	private function getDateModifyFrom(array $requestOptionsFilter): ?DateTime
	{
		return $this->getDateFrom($requestOptionsFilter, static::DATE_MODIFY);
	}

	private function getDateModifyTo(array $requestOptionsFilter): ?DateTime
	{
		return $this->getDateTo($requestOptionsFilter, static::DATE_MODIFY);
	}

	private function getDateFrom(array $requestOptionsFilter, string $key): ?DateTime
	{
		return $this->getDateFromRequestOptions($requestOptionsFilter, $key, true);
	}

	private function getDateTo(array $requestOptionsFilter, string $key): ?DateTime
	{
		return $this->getDateFromRequestOptions($requestOptionsFilter, $key, false);
	}

	private function getDateFromRequestOptions(array $requestOptionsFilter, string $key, bool $isFrom): ?DateTime
	{
		$suffix = $isFrom ? "_from" : "_to";

		$date = $requestOptionsFilter["{$key}{$suffix}"] ?? null;
		if (!$date)
		{
			return null;
		}

		try
		{
			return new DateTime($date);
		}
		catch (ObjectException)
		{
			return null;
		}
	}

	/**
	 * @param array $requestOptionsFilter
	 * @param ActorRole|null $role
	 *
	 * @return list<int>
	 */
	private function getAssigneesOrSignersFromRequest(array $requestOptionsFilter, ?ActorRole $role = null): array
	{
		if ($role === ActorRole::ASSIGNEE || $role === ActorRole::SIGNER)
		{
			return [];
		}

		return $this->getIntArrayFromRequest($requestOptionsFilter, static::SIGNER);
	}
}