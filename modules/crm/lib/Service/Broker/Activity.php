<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Broker;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

final class Activity extends Broker
{
	public function getOwner(int $id): ?ItemIdentifier
	{
		$activity = $this->getById($id);
		if (
			is_array($activity)
			&& isset($activity['OWNER_TYPE_ID'], $activity['OWNER_ID'])
			&& \CCrmOwnerType::IsDefined($activity['OWNER_TYPE_ID'])
			&& $activity['OWNER_ID'] > 0
		)
		{
			return new ItemIdentifier((int)$activity['OWNER_TYPE_ID'], (int)$activity['OWNER_ID']);
		}

		return null;
	}

	public function __construct()
	{
		$eventManager = EventManager::getInstance();

		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnBeforeCrmActivityAdd',
			function ($fields): void {
				if (is_array($fields) && isset($fields['ID']) && is_numeric($fields['ID']) && (int)$fields['ID'] > 0)
				{
					unset($this->cache[(int)$fields['ID']]);
				}
			},
		);
		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnActivityAdd',
			function ($id, $fields): void {
				if (is_numeric($id) && (int)$id > 0 && is_array($fields) && !empty($fields))
				{
					$this->cache[(int)$id] = $fields;
				}
			},
		);

		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnBeforeCrmActivityUpdate',
			function ($id): void {
				if (is_numeric($id) && (int)$id > 0)
				{
					unset($this->cache[(int)$id]);
				}
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

		$eventManager->addEventHandlerCompatible(
			'crm',
			'OnBeforeActivityDelete',
			function ($id): void {
				if (is_numeric($id) && (int)$id > 0)
				{
					unset($this->cache[(int)$id]);
				}
			}
		);

		// currently bindings are not fetched from DB. if this changes, add cache invalidation on bindings change
	}

	protected function loadEntry(int $id)
	{
		$activities = $this->loadEntries([$id]);

		return $activities[$id] ?? null;
	}

	protected function loadEntries(array $ids): array
	{
		$dbResult = \CCrmActivity::GetList(
			[],
			[
				'@ID' => $ids,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			array_merge(
				array_keys(\CCrmActivity::GetFieldsInfo()),
				[
					'STORAGE_TYPE_ID',
					'STORAGE_ELEMENT_IDS',
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
}
