<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\BIConnector;

final class SupersetServiceIntegration
{
	public static function getTableList(): array
	{
		static $result = [];
		if ($result)
		{
			return $result;
		}

		$manager = BIConnector\Manager::getInstance();
		$service = new BIConnector\Services\ApacheSuperset($manager);

		$tableList = $service->getTableList();
		if (!empty($tableList))
		{
			$result = array_map(static fn ($table) => current($table), $tableList);
		}

		return $result;
	}
}
