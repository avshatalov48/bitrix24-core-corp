<?php
namespace Bitrix\Imconnector\Update;

use Bitrix\Main\DI\ServiceLocator;

use Bitrix\ImConnector\Model\StatusConnectorsTable;

class Update2110000
{
	protected const CONNECTOR_ID = 'fbinstagram';

	/**
	 * @return string
	 */
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

		if (empty($connectionsCount))
		{
			$serviceLocator = ServiceLocator::getInstance();
			if($serviceLocator->has('toolsConnector'))
			{
				/** @var \Bitrix\ImConnector\Tools\Connector toolsConnector */
				$toolsConnector = $serviceLocator->get('toolsConnector');
				$toolsConnector->deactivateConnector(self::CONNECTOR_ID);
			}
		}

		return '';
	}
}