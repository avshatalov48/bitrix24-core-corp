<?php

namespace Bitrix\Mobile\Controller\Crm;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class CallTracker extends \Bitrix\Main\Engine\Controller
{
	/**
	 * Get array of deals for call tracker list page
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function listAction(int $limit = 20, int $offset = 0, $updateCounter = false): array
	{
		if (
			!Loader::includeModule('crm')
			|| !class_exists('\Bitrix\Crm\Activity\Provider\CallTracker')
		)
		{
			return [];
		}

		if (\Bitrix\Crm\Settings\LeadSettings::isEnabled())
		{
			return $this->getErrorResult('Wrong crm mode. Only simple crm is supported', 'NOT_SIMPLE_CRM');
		}

		if (!\Bitrix\Crm\Restriction\RestrictionManager::isCallTrackerPermitted())
		{
			return $this->getErrorResult('Not supported for your tariff', 'WRONG_TARIFF');
		}

		if ($limit > 100)
		{
			$limit = 100;
		}

		if ($offset < 0)
		{
			$offset = 0;
		}

		$userID = $this->getCurrentUser()->getId();
		$filter = [
			'IS_RECURRING' => 'N',
			'STAGE_SEMANTIC_ID' => [\Bitrix\Crm\PhaseSemantics::PROCESS],
			'CHECK_PERMISSIONS' => 'Y',
		];

		$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
			\CCrmOwnerType::Deal,
			\Bitrix\Crm\Counter\EntityCounterType::CURRENT,
			$userID
		);

		$filter = array_merge(
			$filter,
			$counter->prepareEntityListFilter([
				'MASTER_ALIAS' => \CCrmDeal::TABLE_ALIAS,
				'MASTER_IDENTITY' => 'ID',
				'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
			])
		);

		$navOptions = [
			'FIELD_OPTIONS' => [
				'ADDITIONAL_FIELDS' => [
					'ACTIVITY',
				],
			],
			'QUERY_OPTIONS' => [
				'LIMIT' => $limit,
				'OFFSET' => $offset,
			],
		];

		$dealIds = [];

		$dealActivityList = \CCrmActivity::GetEntityList(
			\CCrmOwnerType::Deal,
			$userID,
			'asc',
			$filter,
			false,
			$navOptions
		);
		while ($dealActivityItem = $dealActivityList->Fetch())
		{
			$dealIds[] = $dealActivityItem['ID'];
		}

		$result = [
			'items' => $this->getItemsById($dealIds),
		];

		if ($updateCounter)
		{
			$this->updateCounter();
		}

		return $result;
	}

	/**
	 * Get actual deal info for list by deal id
	 *
	 * @param int $id
	 * @return array
	 */
	public function getAction(int $id): array
	{
		if (
			!Loader::includeModule('crm')
			|| !class_exists('\Bitrix\Crm\Activity\Provider\CallTracker')
		)
		{
			return [];
		}

		if (\Bitrix\Crm\Settings\LeadSettings::isEnabled())
		{
			return $this->getErrorResult('Wrong crm mode. Only simple crm is supported');
		}

		$filter = [
			'IS_RECURRING' => 'N',
			'STAGE_SEMANTIC_ID' => [\Bitrix\Crm\PhaseSemantics::PROCESS],
			'CHECK_PERMISSIONS' => 'Y',
			'ID' => $id,
		];

		$deal = \CCrmDeal::GetListEx([], $filter, false, false, ['ID'])->Fetch();
		if (!$deal)
		{
			return [];
		}

		$result = $this->getItemsById([$deal['ID']]);
		if (!empty($result))
		{
			return array_pop($result);
		}

		return [];
	}

	/**
	 * Load deals info by list of ids
	 * @param array $dealIds
	 * @return array
	 */
	private function getItemsById(array $dealIds): array
	{
		if (empty($dealIds))
		{
			return [];
		}
		$deals = [];

		$dealQuery = \Bitrix\Crm\DealTable::query();
		$dealQuery->whereIn('ID', $dealIds);
		$dealQuery
			->addSelect('ID')
			->addSelect('TITLE')
			->addSelect('COMPANY.LOGO', 'COMPANY_LOGO')
			->addSelect('COMPANY.TITLE', 'COMPANY_TITLE')
			->addSelect('CONTACT.NAME', 'CONTACT_NAME')
			->addSelect('CONTACT.LAST_NAME', 'CONTACT_LAST_NAME')
			->addSelect('CONTACT.PHOTO', 'CONTACT_PHOTO')
		;

		$dealList = $dealQuery->exec();
		while ($dealItem = $dealList->fetch())
		{
			$deal = [
				'id' => $dealItem['ID'],
				'title' => $dealItem['TITLE'],
			];
			if ($dealItem['CONTACT_NAME'] <> '' || $dealItem['CONTACT_LAST_NAME'] <> '')
			{
				$deal['client'] = \CCrmContact::GetFullName(['NAME' => $dealItem['CONTACT_NAME'], 'LAST_NAME' => $dealItem['CONTACT_LAST_NAME']]);
			}
			elseif ($dealItem['COMPANY_TITLE'] <> '')
			{
				$deal['client'] = $dealItem['COMPANY_TITLE'];
			}

			$photoId = null;
			if ($dealItem['CONTACT_PHOTO'] > 0)
			{
				$photoId = $dealItem['CONTACT_PHOTO'];
			}
			elseif ($dealItem['COMPANY_LOGO'] > 0)
			{
				$photoId = $dealItem['COMPANY_LOGO'];
			}
			if ($photoId > 0)
			{
				$resizedPhoto = \CFile::ResizeImageGet($photoId, ['width' => 100, 'height' => 100], BX_RESIZE_IMAGE_PROPORTIONAL);
				if ($resizedPhoto)
				{
					if (mb_substr($resizedPhoto['src'], 0, 1) === '/')
					{
						$deal['photo'] = \CHTTP::URN2URI($resizedPhoto['src']);
					}
					else
					{
						$deal['photo'] = $resizedPhoto['src'];
					}
				}
			}
			$deals[$deal['id']] = $deal;
		}
		$activityBindings = array_map(
			function($dealId) {
				return [
					'OWNER_ID' => $dealId,
					'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				];
			},
			array_keys($deals)
		);

		$dealActivities = [];
		$activitiesList = \CCrmActivity::GetList(
			[],
			[
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
				'BINDINGS' => $activityBindings,
				'=TYPE_ID' => \CCrmActivityType::Provider,
				'=PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
				'=RESPONSIBLE_ID' => $this->getCurrentUser()->getId()
			],
			false,
			false,
			[
				'OWNER_ID',
				'CREATED',
				'DEADLINE',
				'SETTINGS',
				'DIRECTION',
			]
		);
		$deadlineTs = (new \Bitrix\Main\Type\DateTime)
			->toUserTime()
			->setTime(23, 59, 59)
			->getTimestamp()
		;

		while ($activityItem = $activitiesList->Fetch())
		{
			if (!isset($dealActivities[$activityItem['OWNER_ID']]))
			{
				$dealActivities[$activityItem['OWNER_ID']] = [
					'COUNT' => 0,
					'LAST_CREATED' => 0,
					'SETTINGS' => [],
					'DIRECTION' => \CCrmActivityDirection::Undefined,
				];
			}
			$activityDeadlineTs = (new \Bitrix\Main\Type\DateTime($activityItem['DEADLINE']))->getTimestamp();
			if ($activityDeadlineTs <= $deadlineTs)
			{ // overdue
				$dealActivities[$activityItem['OWNER_ID']]['COUNT']++;
			}

			$dateCreate = (new \Bitrix\Main\Type\DateTime($activityItem['CREATED']))
				->toUserTime()
				->getTimestamp()
			;
			if ($dealActivities[$activityItem['OWNER_ID']]['LAST_CREATED'] < $dateCreate)
			{ // get data from last activity
				$dealActivities[$activityItem['OWNER_ID']]['LAST_CREATED'] = $dateCreate;
				$dealActivities[$activityItem['OWNER_ID']]['SETTINGS'] = $activityItem['SETTINGS'];
			}
			$dealActivities[$activityItem['OWNER_ID']]['DIRECTION'] = $activityItem['DIRECTION'];
		}

		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$dateFormat = $culture->getShortDateFormat();
		$timeFormat = $culture->getShortTimeFormat();
		$todayTs = (new \Bitrix\Main\Type\DateTime())
			->toUserTime()
			->setTime(0, 0, 0)
			->getTimestamp()
		;

		$items = [];
		foreach ($dealIds as $dealId)
		{
			$deal = $deals[$dealId];
			if (!$deal)
			{
				continue;
			}

			$activity = $dealActivities[$dealId];
			if ($activity['COUNT'])
			{
				$deal['activityCount'] = (string)$activity['COUNT'];
			}
			if ($activity['LAST_CREATED'])
			{
				$deal['date'] = date(
					$activity['LAST_CREATED'] < $todayTs ? $dateFormat : $timeFormat,
					$activity['LAST_CREATED']
				);
			}
			if ($activity['DIRECTION'] != \CCrmActivityDirection::Undefined)
			{
				$deal['direction'] = $activity['DIRECTION'];
			}
			if ($activity['SETTINGS'] && isset($activity['SETTINGS']['DURATION']) && $activity['SETTINGS']['DURATION'] > 0)
			{
				$minutes = (int)($activity['SETTINGS']['DURATION'] / 60);
				$seconds = (int)($activity['SETTINGS']['DURATION'] % 60);
				$deal['duration'] = $minutes > 0 ?
					Loc::getMessage('CRM_CALL_TRACKER_DURATION_LONG', ['#MIN#' => $minutes, '#SEC#' => $seconds]) :
					Loc::getMessage('CRM_CALL_TRACKER_DURATION_SHORT', ['#SEC#' => $seconds]);
			}

			$items[] = $deal;
		}

		return $items;
	}

	/**
	 * Add phone number from deal to ignored list
	 *
	 * @param int $id
	 * @return array
	 */
	public function addToIgnoredAction(int $id): array
	{
		try
		{
			if (Loader::includeModule('crm'))
			{
				\Bitrix\Crm\Exclusion\Manager::excludeEntity(\CCrmOwnerType::Deal, $id);
			}
		}
		catch (\Bitrix\Main\SystemException $e)
		{
			return $this->getErrorResult($e->getMessage());
		}

		return [];
	}

	/**
	 * Postpone call tracker activity to some $offset hours
	 *
	 * @param int $id
	 * @param int $offset
	 * @return array
	 */
	public function postponeAction(int $id, $offset = 24): array
	{
		global $APPLICATION;
		if (Loader::includeModule('crm'))
		{
			if (\CCrmAuthorizationHelper::CheckUpdatePermission(\CCrmOwnerType::DealName, $id))
			{
				$activities = $this->getActiveActivitiesIds($id);
				foreach ($activities as $activityId)
				{
					$postponeResult = \CCrmActivity::Postpone($activityId, $offset * 60 * 60);
					if (empty($postponeResult) && $APPLICATION->GetException())
					{
						return $this->getErrorResult($APPLICATION->GetException());
					}
				}
			}
			else
			{
				return $this->getErrorResult(Loc::getMessage("CRM_CALL_TRACKER_INSUFFICIENT_RIGHTS"));
			}
		}

		return [];
	}

	/**
	 * Get ids of not completed activities
	 * @param int $dealId
	 * @return array
	 */
	protected function getActiveActivitiesIds(int $dealId): array
	{
		$result = [];
		if (!class_exists('\Bitrix\Crm\Activity\Provider\CallTracker'))
		{
			return [];
		}
		$activityCollection = \CCrmActivity::GetList(
			[
				'ID' => 'ASC',
			],
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
				'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				'OWNER_ID' => $dealId,
				'CHECK_PERMISSIONS' => 'Y',
				'COMPLETED' => 'N',
			],
			false,
			false,
			['ID']
		);
		while ($activity = $activityCollection->Fetch())
		{
			$result[] = $activity['ID'];
		}

		return $result;
	}

	private function updateCounter(): void
	{
		$userId = $this->getCurrentUser()->getId();
		$counter = \Bitrix\Crm\Counter\EntityCounterFactory::createCallTrackerCounter($userId);
		$counter->synchronizePostponed();
	}

	private function getErrorResult(string $errorMessage, string $errorCode = '')
	{
		return [
			'error' => $errorCode,
			'error_description' => $errorMessage,
		];
	}
}