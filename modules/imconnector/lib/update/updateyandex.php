<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\DI\ServiceLocator;

use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Model\StatusConnectorsTable;

final class UpdateYandex extends Stepper
{
	protected const PORTION = 5;
	protected const OPTION_NAME = 'imconnector_old_yandex_channels_to_delete';
	protected const CONNECTOR_ID = 'yandex';
	protected static $moduleId = 'imconnector';

	public static function deleteConnector(): string
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
			self::bind();
		}
		else
		{
			self::deactivateConnector();
		}

		return '';
	}

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
}
