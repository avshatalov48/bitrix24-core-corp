<?php

namespace Bitrix\BIConnector\Superset\ExternalSource;

use Bitrix\BIConnector\ExternalSource\SourceManager;

final class SourceRepository
{
	/**
	 * @return Source[]
	 */
	public static function getSources(): array
	{
		$result = CrmTracking\SourceProvider::getSources();
		if (SourceManager::is1cConnectionsAvailable())
		{
			$result = Source1C\SourceProvider::getSources() + $result;
		}

		return $result;
	}
}
