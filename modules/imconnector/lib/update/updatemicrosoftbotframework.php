<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

use Bitrix\UI;

use Bitrix\ImBot\Bot\Support24;

use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Model\StatusConnectorsTable;

use Bitrix\ImOpenLines\Model\QueueTable;

final class UpdateMicrosoftBotFramework extends Stepper
{
	protected const ARTICLE_CODE = '11382470';
	protected const PORTION = 5;
	protected const OPTION_NAME = 'imconnector_old_botframework_channels_to_delete';
	protected const CONNECTOR_ID = 'botframework';
	protected static $moduleId = 'imconnector';

	public static function deleteConnectorStep1(): string
	{
		$isSend = self::sendNotifications();

		if($isSend === false)
		{
			self::deactivateConnector();
		}

		return '';
	}


	public static function deleteConnectorStep2(): string
	{
		$connectionsCount = StatusConnectorsTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=CONNECTOR' => self::CONNECTOR_ID,
				'=ACTIVE' => 'Y',
			],
			'count_total' => true
		])->getCount();

		if ($connectionsCount > 0)
		{
			self::sendNotifications(false);
			self::bind();
		}
		else
		{
			self::deactivateConnector();
		}

		return '';
	}

	/*public function execute(array &$result): bool
	{
		$return = false;

		if (Loader::includeModule(static::$moduleId))
		{
			$status = $this->loadCurrentStatus();

			if ($status['count'] > 0)
			{
				$result['steps'] = '';
				$result['count'] = $status['count'];

				$cursor = StatusConnectorsTable::getList([
					'select' => ['ID', 'LINE'],
					'filter' => [
						'=CONNECTOR' => self::CONNECTOR_ID,
						'=ACTIVE' => 'Y',
					],
					'offset' => 0,
					'limit' => self::PORTION,
					'order' => ['ID' => 'ASC'],
				]);

				$found = false;
				while ($row = $cursor->fetch())
				{
					$deleteResult = Connector::delete($row['LINE'], self::CONNECTOR_ID);

					if (!$deleteResult->isSuccess())
					{
						Status::delete(self::CONNECTOR_ID, $row['LINE']);
					}

					$status['lastId'] = $row['ID'];
					$status['number']++;
					$found = true;
				}

				if ($found)
				{
					Option::set(static::$moduleId, self::OPTION_NAME, serialize($status));
					$return = true;
				}

				$result['steps'] = $status['number'];

				if ($found === false)
				{
					self::deactivateConnector();
					Option::delete(static::$moduleId, ['name' => self::OPTION_NAME]);
				}
			}
			else
			{
				self::deactivateConnector();
			}
		}

		return $return;
	}*/

	public function execute(array &$result): bool
	{
		$return = false;

		if (Loader::includeModule(static::$moduleId))
		{
			$status = $this->loadCurrentStatus();

			if ($status['count'] > 0)
			{
				$result['steps'] = '';
				$result['count'] = $status['count'];

				$cursor = StatusConnectorsTable::getList([
					'select' => ['ID', 'LINE'],
					'filter' => [
						'=CONNECTOR' => self::CONNECTOR_ID,
						'=ACTIVE' => 'Y',
					],
					'offset' => 0,
					'limit' => self::PORTION,
					'order' => ['ID' => 'ASC'],
				]);

				$found = false;
				while ($row = $cursor->fetch())
				{
					Status::delete(self::CONNECTOR_ID, $row['LINE']);

					$status['lastId'] = $row['ID'];
					$status['number']++;
					$found = true;
				}

				if ($found)
				{
					Option::set(static::$moduleId, self::OPTION_NAME, serialize($status));
					$return = true;
				}

				$result['steps'] = $status['number'];

				if ($found === false)
				{
					self::deactivateConnector();
					Option::delete(static::$moduleId, ['name' => self::OPTION_NAME]);
				}
			}
			else
			{
				self::deactivateConnector();
			}
		}

		return $return;
	}

	/**
	 * @return array|mixed
	 */
	public function loadCurrentStatus()
	{
		$status = Option::get(static::$moduleId, self::OPTION_NAME, '');
		$status = ($status !== '' ? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$count = StatusConnectorsTable::getList([
				'select' => ['ID', 'LINE'],
				'filter' => [
					'=CONNECTOR' => self::CONNECTOR_ID,
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

	protected static function deactivateConnector(): void
	{
		$serviceLocator = ServiceLocator::getInstance();
		if($serviceLocator->has('toolsConnector'))
		{
			/** @var \Bitrix\ImConnector\Tools\Connector toolsConnector */
			$toolsConnector = $serviceLocator->get('toolsConnector');
			$toolsConnector->deactivateConnector(self::CONNECTOR_ID);
		}
	}

	/**
	 * @return bool
	 */
	protected static function sendNotifications($notice = true): bool
	{
		$result = false;
		$idMessage = 'IMCONNECTOR_UPDATER_NOTICE_BOTFRAMEWORK_';
		if($notice === false)
		{
			$idMessage = 'IMCONNECTOR_UPDATER_DISCONNECT_OLD_BOTFRAMEWORK_';
		}

		if (
			Loader::includeModule('imopenlines')
			&& Loader::includeModule('imconnector')
			&& Loader::includeModule('ui')
		)
		{
			$activeConnections = StatusConnectorsTable::getList([
				'select' => ['LINE'],
				'filter' => [
					'=CONNECTOR' => self::CONNECTOR_ID,
					'=ACTIVE' => 'Y',
					'=CONNECTION' => 'Y',
					'=REGISTER' => 'Y',
				],
			]);

			while ($row = $activeConnections->fetch())
			{
				$lineIds[] = $row['LINE'];
			}

			if(!empty($lineIds))
			{
				if (
					Loader::includeModule('bitrix24')
					&& Loader::includeModule('imbot')
				)
				{
					$lineIds = array_unique($lineIds);

					$queueLine = QueueTable::getList([
						'select' => ['USER_ID'],
						'filter' => [
							'=CONFIG_ID' => $lineIds,
						],
					]);

					while ($row = $queueLine->fetch())
					{
						$userIds[] = $row['USER_ID'];
					}

					if (empty($userIds))
					{
						$userIds = ['ADMIN'];
					}
					else
					{
						$admins = Support24::getAdministrators();
						$userIds = array_merge($userIds, $admins);
						$userIds = array_unique($userIds);
					}

					foreach ($userIds as $userId)
					{
						Support24::sendMessage([
							'DIALOG_ID' => $userId,
							'MESSAGE' => Loc::getMessage($idMessage . 'CHAT', [
								'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(self::ARTICLE_CODE) . ']',
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
						'MESSAGE' => Loc::getMessage($idMessage . 'ADMIN_NOTIFY', [
							'#HREF#' => UI\Util::getArticleUrlByCode(self::ARTICLE_CODE),
						]),
					]);
				}

				$result = true;
			}
		}

		return $result;
	}
}
