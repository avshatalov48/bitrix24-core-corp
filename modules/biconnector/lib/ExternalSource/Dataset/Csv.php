<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset;

use Bitrix\BIConnector;

final class Csv extends Base
{
	protected function getResultTableName(): string
	{
		return $this->dataset->getName();
	}

	public function getSqlTableAlias(): string
	{
		return sprintf(
			'%s%s',
			strtoupper($this->dataset->getType()),
			strtoupper($this->dataset->getName())
		);
	}

	protected function getConnectionTableName(): string
	{
		return BIConnector\ExternalSource\Source\Csv::TABLE_NAME_PREFIX . $this->dataset->getName();
	}
}
