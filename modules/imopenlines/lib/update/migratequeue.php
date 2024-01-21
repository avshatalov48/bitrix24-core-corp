<?php
namespace Bitrix\Imopenlines\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Update\Stepper;

Loc::loadMessages(__FILE__);

final class MigrateQueue extends Stepper
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

		$ormParams = [
			'select' => ['*'],
			'filter' => [
				'=ITEM_TYPE' => \Bitrix\Im\V2\Chat::IM_TYPE_OPEN_LINE,
				'<SESSION.STATUS' => \Bitrix\ImOpenLines\Session::STATUS_ANSWER
			],
			'runtime' => [
				new \Bitrix\Main\Entity\ReferenceField(
					'SESSION',
					'\Bitrix\ImOpenLines\Model\SessionTable',
					[
						"=ref.CHAT_ID" => "this.ITEM_CID",
					],
					["join_type" => "INNER"]
				),
			],
			'group' => [
				'USER_ID',
				'ITEM_TYPE',
				'ITEM_ID'
			],
		];

		if (!isset($result['steps']))
		{
			$result = [
				'steps' => 0,
				'count' => \Bitrix\Im\Model\RecentTable::getList($ormParams)->getSelectedRowsCount(),
			];
		}
		$ormParams['limit'] = self::CHUNK_SIZE;

		$oldRecent = \Bitrix\Im\Model\RecentTable::getList($ormParams);

		$newRecent = [];
		$removeRecent = [];
		foreach ($oldRecent->fetchAll() as $recent)
		{
			$newRecent[] = [
				'USER_ID' => $recent['USER_ID'],
				'CHAT_ID' => $recent['ITEM_CID'],
				'MESSAGE_ID' => $recent['ITEM_MID'],
				'SESSION_ID' => $recent['ITEM_OLID'],
				'DATE_CREATE' => $recent['DATE_MESSAGE'],
			];

			$removeRecent[] = [
				'USER_ID' => $recent['USER_ID'],
				'ITEM_TYPE' => $recent['ITEM_TYPE'],
				'ITEM_ID' => $recent['ITEM_ID']
			];

			$result['steps']++;
		}

		\Bitrix\ImOpenLines\Model\RecentTable::multiplyInsertWithoutDuplicate($newRecent, ['DEADLOCK_SAFE' => true]);

		foreach ($newRecent as $recent)
		{
			$relations = \CIMChat::GetRelationById($recent['CHAT_ID'], false, true, false);
			foreach ($relations as $relation)
			{
				$user = \Bitrix\Im\User::getInstance($relation['USER_ID']);
				if (!$user->isConnector() && !$user->isBot())
				{
					\Bitrix\Im\Model\RelationTable::deleteByFilter([
						'CHAT_ID' => $recent['CHAT_ID'],
						'USER_ID' => $relation['USER_ID'],
					]);

					\Bitrix\Im\Model\MessageUnreadTable::deleteByFilter([
						'CHAT_ID' => $recent['CHAT_ID'],
						'USER_ID' => $relation['USER_ID'],
					]);
				}
			}
		}

		foreach ($removeRecent as $item)
		{
			\Bitrix\Im\Model\RecentTable::delete($item);
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
		return Loc::getMessage("IMOL_UPDATE_MIGRATE_QUEUE") ?? '';
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
