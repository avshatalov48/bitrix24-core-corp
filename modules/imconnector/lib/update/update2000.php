<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM;
use Bitrix\Rest;
use Bitrix\ImConnector;

final class Update2000 extends Main\Update\Stepper
{
	private const PORTION = 30;
	private const OPTION_NAME = 'imconnector_check_custom_connectors';
	protected static $moduleId = 'imconnector';

	public function execute(array &$option): bool
	{
		$return = self::FINISH_EXECUTION;

		if (Loader::includeModule(self::$moduleId) && Loader::includeModule('rest'))
		{
			$status = $this->loadCurrentStatus();

			if ($status['count'] > 0)
			{
				$option['progress'] = 1;
				$option['steps'] = '';
				$option['count'] = $status['count'];

				$found = false;
				$cursor = ImConnector\Model\CustomConnectorsTable::getList([
					'select' => [
						'ID',
						'ID_CONNECTOR',
						'REST_APP_ID',
						'REST_APP_ACTIVE' => 'REST_APP.ACTIVE',
						'REST_APP_INSTALLED' => 'REST_APP.INSTALLED',
					],
					'runtime' => [
						new ORM\Fields\Relations\Reference(
							'REST_APP',
							Rest\AppTable::class,
							['=ref.ID' => 'this.REST_APP_ID'],
							['join_type' => 'LEFT']
						)
					],
					'filter' => [
						'>ID' => $status['lastId'],
					],
					'limit' => self::PORTION,
					'order' => ['ID' => 'ASC'],
				]);
				while ($row = $cursor->fetch())
				{
					if (
						empty($row['REST_APP_ID'])
						|| $row['REST_APP_ACTIVE'] != 'Y'
						|| $row['REST_APP_INSTALLED'] != 'Y'
					)
					{
						ImConnector\Rest\Helper::unRegisterApp([
							'ID' => $row['ID_CONNECTOR'],
							'REST_APP_ID' => $row['REST_APP_ID'],
						]);
					}

					$status['lastId'] = $row['ID'];
					$status['number']++;
					$found = true;
				}

				$option['progress'] = floor($status['number'] * 100 / $status['count']);
				$option['steps'] = $status['number'];

				if ($found)
				{
					Option::set(self::$moduleId, self::OPTION_NAME, serialize($status));
					$return = self::CONTINUE_EXECUTION;
				}
				else
				{
					Option::delete(self::$moduleId, ['name' => self::OPTION_NAME]);
				}
			}
		}

		return $return;
	}

	private function loadCurrentStatus(): array
	{
		$status = Option::get(self::$moduleId, self::OPTION_NAME, '');
		$status = ($status !== '' ? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$status = [
				'lastId' => 0,
				'number' => 0,
				'count' => ImConnector\Model\CustomConnectorsTable::getCount(),
			];
		}
		return $status;
	}
}