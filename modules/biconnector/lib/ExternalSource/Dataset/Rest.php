<?php

namespace Bitrix\BIConnector\ExternalSource\Dataset;

final class Rest extends Base
{
	protected function getResultTableName(): string
	{
		return $this->dataset->getName();
	}

	public function getSqlTableAlias(): string
	{
		return sprintf(
			'%s_%s',
			'EXTERNAL_REST',
			strtoupper($this->dataset->getName())
		);
	}

	protected function getConnectionTableName(): string
	{
		return $this->dataset->getName();
	}
}
