<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\Rest;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\Superset\ExternalSource\Rest;

final class SourceProvider
{
	/**
	 * @return Source[]
	 */
	public static function getSources(): array
	{
		$bindings = ExternalSource\Internal\ExternalSourceRestTable::getList([
			'select' => ['CONNECTOR_ID']
		])
			->fetchAll()
		;
		$connectedConnectorIds = array_flip(array_column($bindings, 'CONNECTOR_ID'));

		$sources = [];
		$restCode = ExternalSource\Type::Rest->value;
		$connections = ExternalSource\Internal\ExternalSourceRestConnectorTable::getList()
			->fetchCollection()
		;

		foreach ($connections as $connection)
		{
			$isConnected = isset($connectedConnectorIds[$connection->getId()]);
			$sources["{$restCode}_{$connection->getId()}"] = new Rest\Source($connection, $isConnected);
		}

		return $sources;
	}
}
