<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset;

final class Source1C extends Base
{
	protected function getResultTableName(): string
	{
		return $this->dataset->getName();
	}

	public function getSqlTableAlias(): string
	{
		return sprintf(
			'%s_%s',
			'SOURCE_1C',
			strtoupper($this->dataset->getName())
		);
	}

	protected function getConnectionTableName(): string
	{
		return $this->dataset->getName();
	}
}
