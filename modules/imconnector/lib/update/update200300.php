<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\ImConnector\Status;
use \Bitrix\Main\Loader,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\Update\Stepper,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\ImConnector\Model\StatusConnectorsTable;

Loc::loadMessages(__FILE__);

final class Update200300 extends Stepper
{
	private const PORTION = 5;
	private const OPTION_NAME = 'imconnector_old_instagram_channels_to_delete';
	protected static $moduleId = 'imconnector';
	protected static $connectorId = 'instagram';

	public function execute(array &$result): bool
	{
		$return = false;

		if (Loader::includeModule(self::$moduleId))
		{
			$status = $this->loadCurrentStatus();

			if ($status['count'] > 0)
			{
				$result['steps'] = '';
				$result['count'] = $status['count'];

				$cursor = StatusConnectorsTable::getList([
					'select' => ['ID', 'LINE'],
					'filter' => [
						'=CONNECTOR' => self::$connectorId,
						'=ACTIVE' => 'Y',
					],
					'offset' => 0,
					'limit' => self::PORTION,
					'order' => array('ID' => 'ASC'),
				]);

				$found = false;
				while ($row = $cursor->fetch())
				{
					$deleteResult = \Bitrix\ImConnector\Connector::delete($row['LINE'], self::$connectorId);

					if (!$deleteResult->isSuccess())
					{
						Status::delete(self::$connectorId, $row['LINE']);
					}

					$status['lastId'] = $row['ID'];
					$status['number']++;
					$found = true;
				}

				if ($found)
				{
					Option::set(self::$moduleId, self::OPTION_NAME, serialize($status));
					$return = true;
				}

				$result['steps'] = $status['number'];

				if ($found === false)
				{
					Option::delete(self::$moduleId, ['name' => self::OPTION_NAME]);
				}
			}
		}

		return $return;
	}

	public function loadCurrentStatus()
	{
		$status = Option::get(self::$moduleId, self::OPTION_NAME, '');
		$status = ($status !== '' ? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$count = StatusConnectorsTable::getList([
				'select' => ['ID', 'LINE'],
				'filter' => [
					'=CONNECTOR' => self::$connectorId,
				],
				'count_total' => true
			])->getCount();

			$status = [
				'lastId' => 0,
				'number' => 0,
				'count' => $count,
			];
		}
		return $status;
	}

	/**
	 * Agent which sends notification about disable old instagram connector.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function sendNotifications(): string
	{
		if (Loader::includeModule('imopenlines') &&
			Loader::includeModule('imconnector') &&
			Loader::includeModule('ui')
		)
		{
			if (Loader::includeModule('bitrix24') && Loader::includeModule('imbot'))
			{
				$activeInstagramConnections = \Bitrix\ImConnector\Model\StatusConnectorsTable::getList([
					'select' => ['LINE'],
					'filter' => [
						'=CONNECTOR' => 'instagram',
						'=ACTIVE' => 'Y',
						'=CONNECTION' => 'Y',
						'=REGISTER' => 'Y',
					],
				]);

				while ($row = $activeInstagramConnections->fetch())
				{
					$lineIds[] = $row['LINE'];
				}

				if (!empty($lineIds))
				{
					$lineIds = array_unique($lineIds);

					$queueLine = \Bitrix\ImOpenLines\Model\QueueTable::getList([
						'select' => ['USER_ID'],
						'filter' => [
							'=CONFIG_ID' => $lineIds,
						],
					]);

					while ($row = $queueLine->fetch())
					{
						$userIds[] = $row['USER_ID'];
					}
				}

				if (empty($userIds))
				{
					$userIds = ['ADMIN'];
				}
				else
				{
					$admins = \Bitrix\ImBot\Bot\Support24::getAdministrators();
					$userIds = array_merge($userIds, $admins);
					$userIds = array_unique($userIds);
				}

				foreach ($userIds as $userId)
				{
					\Bitrix\ImBot\Bot\Support24::sendMessage([
						'DIALOG_ID' => $userId,
						'MESSAGE' => Loc::getMessage('IMCONNECTOR_UPDATER_DISCONNECT_OLD_INSTAGRAM_CHAT', [
							'#A_START#' => '[URL=' . \Bitrix\UI\Util::getArticleUrlByCode('4779109') . ']',
							'#A_END#' => '[/URL]',
						]),
						'SYSTEM' => 'N',
						'URL_PREVIEW' => 'N'
					]);
				}
			}
			else
			{
				\CAdminNotify::Add([
					'MODULE_ID' => 'imconnector',
					'ENABLE_CLOSE' => 'Y',
					'NOTIFY_TYPE' => \CAdminNotify::TYPE_NORMAL,
					'MESSAGE' => Loc::getMessage('IMCONNECTOR_UPDATER_DISCONNECT_OLD_INSTAGRAM_ADMIN_NOTIFY', [
						'#HREF#' => \Bitrix\UI\Util::getArticleUrlByCode('4779109'),
					]),
				]);
			}
		}

		return "";
	}
}