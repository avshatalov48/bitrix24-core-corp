<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	Bitrix\Main\Update\Stepper,
	Bitrix\Main\Entity\ReferenceField,
	Bitrix\Rest\AppTable,
	Bitrix\ImConnector\Rest\Helper,
	Bitrix\ImConnector\Model\CustomConnectorsTable;


final class Update2000 extends Stepper
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

				$runtime = [new ReferenceField(
					'REST_APP',
					AppTable::class,
					['=ref.ID' => 'this.REST_APP_ID'],
					['join_type' => 'LEFT']
				)];

				$found = false;
				$cursor = CustomConnectorsTable::getList([
					'select' => [
						'ID',
						'ID_CONNECTOR',
						'REST_APP_ID',
						'REST_APP.ID'
					],
					'runtime' => $runtime,
					'filter' => [
						'REST_APP.ID' => null,
						'>ID' => $status['lastId'],
					],
					'offset' => 0,
					'limit' => self::PORTION,
					'order' => ['ID' => 'ASC'],
				]);
				while ($row = $cursor->fetch())
				{
					Helper::unRegisterApp([
						'ID' => $row['ID_CONNECTOR'],
						'REST_APP_ID' => $row['REST_APP_ID'],
					]);

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
				'count' => CustomConnectorsTable::getCount(),
			];
		}
		return $status;
	}
}