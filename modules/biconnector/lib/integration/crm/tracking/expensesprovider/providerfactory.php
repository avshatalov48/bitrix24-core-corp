<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesProvider;

use Bitrix\BIConnector\Superset\ExternalSource\CrmTracking\SourceProvider;
use Bitrix\Main\Loader;
use Bitrix\Crm;

class ProviderFactory
{
	/**
	 * Returns array of connected tracking sources, that supports daily expenses report
	 *
	 * @return array<Provider>
	 */
	public static function getAvailableProviders(): array
	{
		if (!Loader::includeModule('crm') || !Loader::includeModule('seo'))
		{
			return [];
		}

		$providers = [];

		/** @var array<array{CODE: string, AD_CLIENT_ID: string, AD_ACCOUNT_ID: string, ...}> $readySources */
		$readySources = Crm\Tracking\Provider::getReadySources();
		$availableCodes = array_keys(SourceProvider::getSources());
		foreach ($readySources as $source)
		{
			if (!in_array($source['CODE'], $availableCodes))
			{
				continue;
			}

			$sourceId = (int)($source['ID'] ?? 0);
			$seoCode = Crm\Tracking\Analytics\Ad::getSeoCodeByCode($source['CODE']);
			if ($seoCode && $sourceId)
			{
				$providers[] = new Provider(
					$sourceId,
					$source['NAME'] ?? 'Unknown',
					$seoCode,
					$source['AD_ACCOUNT_ID'],
					$source['AD_CLIENT_ID']
				);
			}
		}

		return $providers;
	}
}
