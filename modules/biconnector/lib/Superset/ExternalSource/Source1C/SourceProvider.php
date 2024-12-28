<?php

namespace Bitrix\BIConnector\Superset\ExternalSource\Source1C;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BIConnector\Superset\ExternalSource\Source1C;

final class SourceProvider
{
	/**
	 * @return Source[]
	 */
	public static function getSources(): array
	{
		$connections = ExternalSourceTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVE' => 'Y',
				'=TYPE' => ExternalSource\Type::Source1C->value,
			],
			'limit' => 1,
		])->fetchAll();
		$isConnected = count($connections) > 0;

		return [
			ExternalSource\Type::Source1C->value => new Source1C\Source($isConnected),
		];
	}
}
