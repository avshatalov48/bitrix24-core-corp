<?php

namespace Bitrix\TasksMobile\Controller\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

Loader::requireModule('socialnetwork');

class Counter extends Controller
{
	public function configureActions(): array
	{
		return [
			'getByType' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getByRole' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getByTypeAction(int $userId = 0, array $counterTypes = []): array
	{
		$userId = ($userId ?: CurrentUser::get()->getId());
		$counterProvider = Internals\Counter::getInstance($userId);

		if (empty($counterTypes))
		{
			$counterTypes = [
				CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
				CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
				CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
				CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
				CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS,
				CounterDictionary::COUNTER_SCRUM_FOREIGN_COMMENTS,
				CounterDictionary::COUNTER_FLOW_TOTAL,
				CounterDictionary::COUNTER_EFFECTIVE,
			];
		}
		$counters = [];
		foreach ($counterTypes as $type)
		{
			$counters[$type] = $counterProvider->get($type);
		}

		return $this->convertKeysToCamelCase($counters);
	}

	/**
	 * @param int $userId
	 * @param int $groupId
	 * @param string $role
	 * @return array|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function getByRoleAction(int $userId = 0, int $groupId = 0, string $role = Internals\Counter\Role::ALL): ?array
	{
		if (!$this->checkGroupReadAccess($groupId))
		{
			$this->addError(new Error('Group not found or access denied.'));

			return null;
		}

		$userId = ($userId ?: $this->getCurrentUser()->getId());
		$counterInstance = Internals\Counter::getInstance($userId);

		return $counterInstance->getCounters($role, $groupId);
	}

	/**
	 * @param $groupId
	 * @return bool
	 */
	private function checkGroupReadAccess($groupId): bool
	{
		if (!$groupId)
		{
			return true;
		}

		// can we see all tasks in this group?
		$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
			SONET_ENTITY_GROUP,
			[$groupId],
			'tasks',
			'view_all'
		);
		$canViewGroup = (is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId]);
		if (!$canViewGroup)
		{
			// okay, can we see at least our own tasks in this group?
			$featurePerms = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
				SONET_ENTITY_GROUP,
				[$groupId],
				'tasks',
				'view'
			);
			$canViewGroup = (is_array($featurePerms) && isset($featurePerms[$groupId]) && $featurePerms[$groupId]);
		}

		return $canViewGroup;
	}
}
