<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service\Broker;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

final class Activity extends Broker
{
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
			array_keys(\CCrmActivity::GetFieldsInfo()),
		);

		$result = [];
		while ($activity = $dbResult->Fetch())
		{
			$result[(int)$activity['ID']] = $activity;
		}

		return $result;
	}
}
