<?php

namespace Bitrix\BIConnector\Superset\ExternalSource;

use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\Main\Loader;

final class SourceRepository
{
	/**
	 * @return Source[]
	 */
	public static function getSources(): array
	{
		$result = CrmTracking\SourceProvider::getSources();
		if (Loader::includeModule('rest'))
		{
			$result = array_merge(Rest\SourceProvider::getSources(), $result);
		}

		if (SourceManager::is1cConnectionsAvailable())
		{
			$result = Source1C\SourceProvider::getSources() + $result;
		}

		return $result;
	}
}
