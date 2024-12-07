<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Internal\FlowNotificationTable;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;

class SyncAgent
{
	private static bool $processing = false;

	public static function execute($flowId)
	{
		$flowId = (int)$flowId;

		if (self::$processing) {
			return self::getAgentName($flowId);
		}

		self::$processing = true;

		$res = FlowNotificationTable::query()
			->setSelect(['ID', 'FLOW_ID', 'STATUS', 'DATA', 'INTEGRATION_ID'])
			->where('FLOW_ID', $flowId)
			->exec()
		;

		$bizProc = new Manager();
		$deleteIds = [];

		while($row = $res->fetch())
		{
			switch ($row['STATUS'])
			{
				case Item::STATUS_DELETED:
					$bizProc->deleteSmartProcess($row['INTEGRATION_ID']);
					$deleteIds[] = $row['ID'];
					break;
				case Item::STATUS_SYNC:
					$data = Json::decode($row['DATA']);
					$item = Item::toObject($data);
					if ($item)
					{
						$procId = $bizProc->addSmartProcess($item);
						FlowNotificationTable::update(
							$row['ID'],
							[
								'STATUS' => Item::STATUS_ACTIVE,
								'INTEGRATION_ID' => $procId,
							]
						);
					}
					break;
			}
		}

		if (!empty($deleteIds))
		{
			FlowNotificationTable::deleteByFilter([
				'ID' => $deleteIds
			]);
		}

		self::$processing = false;

		return '';
	}

	public function __construct()
	{

	}

	public function addAgent(int $taskId): void
	{
		$res = \CAgent::GetList(
			['ID' => 'DESC'],
			[
				'=NAME' => self::getAgentName($taskId)
			]
		);
		if ($res->Fetch()) {
			return;
		}

		\CAgent::AddAgent(
			self::getAgentName($taskId),
			'tasks',
			'N',
			0,
			'',
			'Y',
			''
		);
	}

	public function removeAgent($taskId): void
	{
		\CAgent::RemoveAgent(self::getAgentName($taskId), 'tasks');
	}

	private static function getAgentName(int $taskId): string
	{
		return static::class . "::execute(". $taskId .");";
	}
}