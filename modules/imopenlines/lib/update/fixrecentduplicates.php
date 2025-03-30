<?php
namespace Bitrix\Imopenlines\Update;

use Bitrix\Im\V2\Relation\DeleteUserConfig;
use Bitrix\ImOpenLines\Model\QueueTable;
use Bitrix\ImOpenLines\Model\RecentTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

Loc::loadMessages(__FILE__);

final class FixRecentDuplicates extends Stepper
{
	private const CHUNK_SIZE = 500;
	protected static $moduleId = "imopenlines";

	public function execute(array &$result)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return Stepper::FINISH_EXECUTION;
		}

		if (!self::disableQueue())
		{
			return __METHOD__. '();';
		}

		if (!isset($result['steps']))
		{
			$result = [
				'steps' => 0,
				'count' => RecentTable::getList()->getSelectedRowsCount(),
				'queues' => []
			];
		}

		$recentRows = RecentTable::getList([
			'select' => [
				'SESSION_ID',
				'USER_ID',
				'CHAT_ID',
			],
			'limit' => self::CHUNK_SIZE,
			'offset' => $result['steps']
		]);
		foreach ($recentRows->fetchAll() as $recentRow)
		{
			$session = SessionTable::getRowById((int)$recentRow['SESSION_ID']);
			if (!isset($result['queues'][$session['CONFIG_ID']]))
			{
				$sessionQueue = QueueTable::getList([
					'select' => [
						'USER_ID'
					],
					'filter' => [
						'=CONFIG_ID' => (int)$session['CONFIG_ID']
					]
				]);

				$result['queues'][$session['CONFIG_ID']] = $sessionQueue->fetchAll();
			}

			$relationsForDelete = [];
			foreach ($result['queues'][$session['CONFIG_ID']] as $operatorItem)
			{
				$relationsForDelete[] = (int)$operatorItem['USER_ID'];
			}

			if (empty($relationsForDelete))
			{
				continue;
			}

			$chat = \Bitrix\Im\V2\Chat::getInstance((int)$recentRow['CHAT_ID']);
			$relations = $chat->getRelations();
			foreach ($relations as $relation)
			{
				if (in_array($relation->getUserId(), $relationsForDelete))
				{
					$config = new DeleteUserConfig(false, false, false);
					$chat->deleteUser($relation->getUserId(), $config);
				}
			}

			$result['steps']++;
		}

		if ($result['steps'] >= $result['count'])
		{
			self::enableQueue();
			return Stepper::FINISH_EXECUTION;
		}

		return Stepper::CONTINUE_EXECUTION;
	}

	public static function getTitle(): string
	{
		return Loc::getMessage('IMOL_UPDATE_MIGRATE_QUEUE');
	}

	private static function disableQueue(): bool
	{
		$agents = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => 'imopenlines',
				'NAME' => '%Bitrix\\ImOpenLines\\Session\\Agent::transferToNextInQueue%'
			]
		);
		if ($row = $agents->Fetch())
		{
			if ($row['RUNNING'] === 'Y')
			{
				return false;
			}
			$updateResult = \CAgent::Update($row['ID'], ['ACTIVE' => 'N']);

			return is_object($updateResult);
		}

		return true;
	}

	private static function enableQueue(): void
	{
		$agents = \CAgent::GetList(
			[],
			[
				'MODULE_ID' => 'imopenlines',
				'NAME' => '%Bitrix\\ImOpenLines\\Session\\Agent::transferToNextInQueue%'
			]
		);
		if ($row = $agents->Fetch())
		{
			\CAgent::Update($row['ID'], ['ACTIVE' => 'Y']);
		}
	}
}
