<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Broker;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use CCrmActivity;
use CCrmOwnerType;

/**
 * @method array|null getById(int $id)
 * @method array[] getBunchByIds(array $ids)
 */
final class Activity extends Broker
{
	protected ?string $eventEntityAdd = 'OnActivityAdd';
	protected ?string $eventEntityDelete = 'OnActivityDelete';

	public function getOwner(int $id): ?ItemIdentifier
	{
		$activity = $this->getById($id);
		if (
			is_array($activity)
			&& isset($activity['OWNER_TYPE_ID'], $activity['OWNER_ID'])
			&& $activity['OWNER_ID'] > 0
			&& CCrmOwnerType::IsDefined($activity['OWNER_TYPE_ID'])
		)
		{
			return new ItemIdentifier((int)$activity['OWNER_TYPE_ID'], (int)$activity['OWNER_ID']);
		}

		return null;
	}

	protected function loadEntry(int $id)
	{
		$activities = $this->loadEntries([$id]);

		return $activities[$id] ?? null;
	}

	protected function loadEntries(array $ids): array
	{
		$dbResult = CCrmActivity::GetList(
			[],
			[
				'@ID' => $ids,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			array_merge(
				array_keys(CCrmActivity::GetFieldsInfo()),
				[
					'STORAGE_TYPE_ID',
					'STORAGE_ELEMENT_IDS',
					'CALENDAR_EVENT_ID',
					'START_TIME',
				],
			),
		);

		$result = [];
		while ($activity = $dbResult->Fetch())
		{
			$result[(int)$activity['ID']] = $activity;
		}

		return $result;
	}

	/**
	 * @override
	 */
	protected function initAdditionalCacheManagementEventHandlers(EventManager $eventManager): void
	{
		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnBeforeCrmActivityAdd',
			function ($fields): void {
				if (is_array($fields) && isset($fields['ID']))
				{
					$this->removeFromCache((int)($fields['ID']));
				}
			},
		);

		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnBeforeCrmActivityUpdate',
			function ($id): void {
				$this->removeFromCache((int)($id));
			},
		);

		$eventManager->addEventHandler(
			'crm',
			'OnActivityModified',
			function (Event $event): void {
				$activity = $event->getParameter('current');
				if (is_array($activity) && isset($activity['ID']))
				{
					$this->cache[$activity['ID']] = $activity;
				}
			},
		);

		// currently bindings are not fetched from DB. if this changes, add cache invalidation on bindings change
	}
}
