<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Internal\FlowNotificationTable;

class ConfigRepository
{
	private array $localStorage = [];

	public function readByFlowId(int $flowId): Config
	{
		if (!empty($this->localStorage[$flowId]))
		{
			return $this->localStorage[$flowId];
		}

		$res = FlowNotificationTable::query()
			->setSelect(['FLOW_ID', 'STATUS', 'DATA', 'ID', 'INTEGRATION_ID'])
			->where('FLOW_ID', $flowId)
			->whereNot('STATUS', Config\Item::STATUS_DELETED)
			->exec()
		;

		$this->localStorage[$flowId] = new Config(
			$flowId,
			$this->getItems($res),
		);

		return $this->localStorage[$flowId];
	}

	public function saveNewConfig(Config $config): void
	{
		$this->localStorage[$config->getFlowId()] = $config;

		$items = [];

		foreach ($config->getItems() as $item)
		{
			$items[] = [
				'FLOW_ID' => $config->getFlowId(),
				'STATUS' => Config\Item::STATUS_SYNC,
				'INTEGRATION_ID' => 0,
				'DATA' => Json::encode($item->toArray()),
			];
		}

		if (empty($items))
		{
			return;
		}

		FlowNotificationTable::addMulti($items, true);
	}

	public function removeByFlowId(int $flowId): void
	{
		unset($this->localStorage[$flowId]);

		FlowNotificationTable::updateByFilter(
			['=FLOW_ID' => $flowId],
			['STATUS' => Config\Item::STATUS_DELETED]
		);
	}

	private function getItems(Result $res): array
	{
		$items = [];

		while ($row = $res->fetch())
		{
			$decoded = Json::decode($row['DATA']);
			$item = Config\Item::toObject($decoded);
			if ($item)
			{
				$item
					->setId($row['ID'])
					->setIntegrationId($row['INTEGRATION_ID'])
				;

				$items[] = $item;
			}
		}

		return $items;
	}
}