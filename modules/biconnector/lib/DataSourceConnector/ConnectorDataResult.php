<?php

namespace Bitrix\BIConnector\DataSourceConnector;

use Bitrix\Main\Result;

final class ConnectorDataResult extends Result
{
	private ?ConnectorDto $connectorData;

	public function setConnectorData(ConnectorDto $connectorData): void
	{
		$this->connectorData = $connectorData;
	}

	public function getConnectorData(): ?ConnectorDto
	{
		return $this->connectorData;
	}
}
