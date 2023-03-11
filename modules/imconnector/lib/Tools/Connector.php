<?php
namespace Bitrix\ImConnector\Tools;

use Bitrix\Main\Config\Option;

use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Model\StatusConnectorsTable;

class Connector
{
	/**
	 * @param $connectorId
	 */
	public function deactivateConnector($connectorId): void
	{
		$listConnector = explode(',', Option::get('imconnector', 'list_connector'));

		foreach($listConnector as $key => $connector)
		{
			if ($connector === $connectorId)
			{
				unset($listConnector[$key]);
			}
		}

		Option::set('imconnector', 'list_connector', implode(',', array_unique($listConnector)));

		$cursor = StatusConnectorsTable::getList([
			'select' => ['ID', 'LINE'],
			'filter' => [
				'=CONNECTOR' => $connectorId,
			],
			'order' => ['ID' => 'ASC'],
		]);

		while ($row = $cursor->fetch())
		{
			Status::delete($connectorId, (int)$row['LINE']);
		}
	}
}